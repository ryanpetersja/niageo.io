<?php

namespace App\Services;

use App\Models\ClientRepository;
use App\Models\CodeReview;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * AI review of pull requests for the person deploying them: which to merge, what each one does
 * and how, and what has to happen outside the repository's standard deploy script.
 */
class CodeReviewService
{
    /** Diff budget per review, in characters, so a large PR set stays within one model call. */
    public const DIFF_BUDGET = 180000;

    /** Diff budget per file; files that matter for deployment (migrations, config…) get the full budget. */
    public const FILE_BUDGET = 6000;

    public const DEPLOY_SENSITIVE = [
        '#^database/migrations/#',
        '#^database/seeders/#',
        '#^config/#',
        '#^routes/#',
        '#^app/Console/#',
        '#^app/Jobs/#',
        '#^app/Providers/#',
        '#^\.env#',
        '#^composer\.json$#',
        '#^package\.json$#',
        '#^vite\.config#',
        '#^Dockerfile#',
        '#^docker-compose#',
        '#^\.github/workflows/#',
        '#^(forge|deploy|Envoy)\.#i',
        '#^public/\.htaccess$#',
        '#^storage/#',
    ];

    public function __construct(
        private GitHubService $github,
        private ClaudeService $claude,
    ) {}

    /**
     * Fetch the pull requests, review them with Claude and store the result.
     *
     * @param  int[]  $numbers  pull request numbers in the repository
     *
     * @throws \RuntimeException when GitHub or Claude fails
     */
    public function generate(ClientRepository $repo, array $numbers, ?User $user = null): CodeReview
    {
        if (! $this->github->isConfigured()) {
            throw new \RuntimeException('GitHub is not connected. An admin needs to set GITHUB_TOKEN on the server.');
        }

        $pulls = [];
        $budget = self::DIFF_BUDGET;
        foreach (array_unique(array_map('intval', $numbers)) as $number) {
            $pull = $this->github->fetchPullRequest($repo->owner, $repo->repo_name, $number);
            $files = $this->github->fetchPullRequestFiles($repo->owner, $repo->repo_name, $number);
            $pull['files'] = $files;
            [$pull['diff_text'], $budget] = $this->renderFiles($files, $budget);
            $pulls[] = $pull;
        }

        if ($pulls === []) {
            throw new \RuntimeException('Pick at least one pull request to review.');
        }

        $sections = $this->review($repo, $pulls);

        $review = CodeReview::create([
            'client_id' => $repo->client_id,
            'client_repository_id' => $repo->id,
            'created_by' => $user?->id,
            'title' => $this->title($pulls),
            'pull_requests' => array_map(fn ($p) => [
                'number' => $p['number'],
                'title' => $p['title'],
                'author' => $p['author'],
                'state' => $p['state'],
                'draft' => $p['draft'],
                'base' => $p['base'],
                'head' => $p['head'],
                'merged_at' => $p['merged_at'],
                'mergeable_state' => $p['mergeable_state'],
                'url' => $p['url'],
                'changed_files' => $p['changed_files'],
                'additions' => $p['additions'],
                'deletions' => $p['deletions'],
                'deploy_sensitive_files' => array_values(array_filter(array_column($p['files'], 'filename'), fn ($f) => $this->isDeploySensitive($f))),
            ], $pulls),
            'sections' => $sections,
            'model' => config('services.anthropic.model'),
        ]);

        return $review;
    }

    /**
     * Ask Claude for the review and shape its JSON into the stored sections.
     *
     * @return array{summary: string, pull_requests: array, deployment_steps: array, database_updates: array, post_deploy_checks: array}
     */
    public function review(ClientRepository $repo, array $pulls): array
    {
        $script = trim((string) $repo->deploy_script);
        $scriptBlock = $script === ''
            ? '(no standard deploy script recorded: assume nothing is automated and list every step, including composer install, npm build and migrations)'
            : $script;

        $system = <<<PROMPT
You are a senior engineer reviewing pull requests for a small agency before they are deployed to a client's production site. The reader is the developer who will merge and deploy; they are experienced, busy and want a straight answer. Be succinct and concrete. Prefer short sentences and bullet-sized items. Do not pad, do not restate the diff, do not praise.

Hard rules:
- Judge from the diff. If something cannot be known from it, say so instead of guessing.
- The repository's standard deploy script runs automatically on every deploy. NEVER list a step it already performs (for example git pull, composer install, npm build, php artisan migrate, PHP-FPM reload, when the script does those). Only list what is NOT covered: new environment variables, config:cache / queue:restart / scheduler or cron changes, storage links, third-party service setup, data backfills or one-off commands, manual verification, order of merging.
- Database updates: one item per migration or data change, naming the table and columns, whether it is additive or destructive, whether it needs a backfill, and whether it can run safely on live data.
- Recommendations: "merge" when the change is sound and complete; "merge_with_caution" when it works but has a risk the deployer should watch; "hold" when it is incomplete, conflicts, breaks something, or is unsafe for production.
- Always respond with valid JSON only — no markdown fences, no commentary.
PROMPT;

        $user = "Repository: {$repo->full_name} (default branch {$repo->default_branch}).\n\n"
            . "STANDARD DEPLOY SCRIPT (runs automatically on every deploy; never list these steps):\n{$scriptBlock}\n\n"
            . "PULL REQUESTS TO REVIEW:\n\n" . implode("\n\n", array_map(fn ($p) => $this->renderPull($p), $pulls)) . "\n\n"
            . <<<'PROMPT'
Return JSON in exactly this shape:
{
  "summary": "2-4 sentences: what this batch delivers, what to merge, the one thing to watch.",
  "pull_requests": [
    {
      "number": 123,
      "recommendation": "merge" | "merge_with_caution" | "hold",
      "headline": "one line: what it does",
      "functionality": "2-5 sentences: the functionality delivered and how it is achieved (key classes, routes, jobs, tables), written for a developer",
      "reasons": ["why this recommendation, one point each"],
      "risks": ["things that could go wrong on production, or []"]
    }
  ],
  "merge_order": ["#123 then #124 because …", "or [] when order does not matter"],
  "deployment_steps": ["steps outside the standard deploy script, in order, imperative, with the exact command or setting; [] when none"],
  "database_updates": ["one per migration or data change; [] when none"],
  "post_deploy_checks": ["quick checks to confirm the deploy worked, specific to these changes"]
}
PROMPT;

        $body = $this->claude->send([
            'model' => config('services.anthropic.model', 'claude-sonnet-4-5-20250929'),
            'max_tokens' => 8192,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ], 180, 'code_review');

        $text = '';
        foreach ($body['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text) ?? $text);
        $data = json_decode($text, true);

        if (! is_array($data)) {
            Log::error('Code review: Claude returned invalid JSON', ['text' => mb_substr($text, 0, 2000)]);
            throw new \RuntimeException('The AI returned an unreadable review. Please try again.');
        }

        return $this->shape($data, $pulls);
    }

    /** Normalise the model's JSON: every section present, every PR accounted for, lists of strings. */
    protected function shape(array $data, array $pulls): array
    {
        $list = fn ($value) => array_values(array_filter(array_map(
            fn ($v) => is_scalar($v) ? trim((string) $v) : '',
            is_array($value) ? $value : []
        ), fn ($v) => $v !== ''));

        $byNumber = [];
        foreach ((array) ($data['pull_requests'] ?? []) as $pr) {
            if (is_array($pr) && isset($pr['number'])) {
                $byNumber[(int) $pr['number']] = $pr;
            }
        }

        $prs = [];
        foreach ($pulls as $pull) {
            $pr = $byNumber[$pull['number']] ?? [];
            $recommendation = strtolower(trim((string) ($pr['recommendation'] ?? '')));
            $prs[] = [
                'number' => $pull['number'],
                'recommendation' => array_key_exists($recommendation, CodeReview::RECOMMENDATIONS) ? $recommendation : 'hold',
                'headline' => trim((string) ($pr['headline'] ?? $pull['title'])),
                'functionality' => trim((string) ($pr['functionality'] ?? 'The AI did not describe this pull request.')),
                'reasons' => $list($pr['reasons'] ?? []),
                'risks' => $list($pr['risks'] ?? []),
            ];
        }

        return [
            'summary' => trim((string) ($data['summary'] ?? '')),
            'pull_requests' => $prs,
            'merge_order' => $list($data['merge_order'] ?? []),
            'deployment_steps' => $list($data['deployment_steps'] ?? []),
            'database_updates' => $list($data['database_updates'] ?? []),
            'post_deploy_checks' => $list($data['post_deploy_checks'] ?? []),
        ];
    }

    protected function renderPull(array $p): string
    {
        $state = $p['state'] . ($p['draft'] ? ', draft' : '') . ($p['merged_at'] ? ' on ' . $p['merged_at'] : '');
        $body = $p['body'] === '' ? '(no description)' : mb_substr($p['body'], 0, 3000);

        return "=== PR #{$p['number']}: {$p['title']}\n"
            . "Author: {$p['author']} | State: {$state} | {$p['head']} → {$p['base']} | GitHub mergeable_state: {$p['mergeable_state']}\n"
            . "{$p['commits']} commits, {$p['changed_files']} files, +{$p['additions']} −{$p['deletions']}\n"
            . "Description:\n{$body}\n"
            . "Changed files and diff:\n{$p['diff_text']}";
    }

    /**
     * File list plus patches, deploy-sensitive files first and in full, within the remaining budget.
     *
     * @return array{0: string, 1: int} rendered text and the budget left
     */
    protected function renderFiles(array $files, int $budget): array
    {
        usort($files, fn ($a, $b) => $this->isDeploySensitive($b['filename']) <=> $this->isDeploySensitive($a['filename']));

        $out = [];
        foreach ($files as $file) {
            $head = "--- {$file['filename']} ({$file['status']}, +{$file['additions']} −{$file['deletions']})";
            $patch = $file['patch'];
            $sensitive = $this->isDeploySensitive($file['filename']);
            $cap = $sensitive ? self::DIFF_BUDGET : self::FILE_BUDGET;

            if ($patch === '') {
                $out[] = $head . ' [no patch available: binary or too large]';
                continue;
            }
            if ($budget <= 0) {
                $out[] = $head . ' [patch omitted: review size limit reached]';
                continue;
            }

            $allowed = min($cap, $budget);
            if (mb_strlen($patch) > $allowed) {
                $patch = mb_substr($patch, 0, $allowed) . "\n[… patch truncated]";
            }
            $budget -= mb_strlen($patch);
            $out[] = $head . "\n" . $patch;
        }

        return [implode("\n", $out), $budget];
    }

    public function isDeploySensitive(string $filename): bool
    {
        foreach (self::DEPLOY_SENSITIVE as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    protected function title(array $pulls): string
    {
        if (count($pulls) === 1) {
            return 'PR #' . $pulls[0]['number'] . ': ' . mb_substr($pulls[0]['title'], 0, 120);
        }
        $numbers = array_map(fn ($p) => '#' . $p['number'], $pulls);

        return count($pulls) . ' pull requests (' . implode(', ', array_slice($numbers, 0, 6)) . (count($numbers) > 6 ? ', …' : '') . ')';
    }
}
