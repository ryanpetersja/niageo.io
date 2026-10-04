<?php

namespace App\Services;

use App\Models\ClientRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    private string $baseUrl = 'https://api.github.com';

    private function token(): string
    {
        $token = config('services.github.token');

        if (empty($token)) {
            throw new \RuntimeException('GitHub token is not configured. Set GITHUB_TOKEN in your .env file and run php artisan config:cache.');
        }

        return $token;
    }

    public function fetchCommits(string $owner, string $repo, string $since, string $until, ?string $branch = null): array
    {
        $commits = [];
        $page = 1;
        $perPage = 100;

        do {
            $params = [
                'since' => $since,
                'until' => $until,
                'per_page' => $perPage,
                'page' => $page,
            ];

            if ($branch) {
                $params['sha'] = $branch;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept' => 'application/vnd.github+json',
            ])->get("{$this->baseUrl}/repos/{$owner}/{$repo}/commits", $params);

            if ($response->failed()) {
                Log::warning("GitHub API failed for {$owner}/{$repo}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                break;
            }

            $items = $response->json();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $commits[] = [
                    'sha' => substr($item['sha'], 0, 7),
                    'message' => $item['commit']['message'] ?? '',
                    'author_name' => $item['commit']['author']['name'] ?? 'Unknown',
                    'author_email' => $item['commit']['author']['email'] ?? '',
                    'date' => $item['commit']['author']['date'] ?? '',
                    'repo' => "{$owner}/{$repo}",
                ];
            }

            $page++;
        } while (count($items) === $perPage);

        return $commits;
    }

    public function fetchCommitsForClient(int $clientId, string $since, string $until): array
    {
        $repos = ClientRepository::where('client_id', $clientId)
            ->where('is_active', true)
            ->get();

        $allCommits = [];

        foreach ($repos as $repo) {
            $commits = $this->fetchCommits($repo->owner, $repo->repo_name, $since, $until, $repo->default_branch);
            $allCommits = array_merge($allCommits, $commits);
        }

        usort($allCommits, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $allCommits;
    }

    /** Most items one activity lookup returns (one invoice line each). */
    public const ACTIVITY_LIMIT = 100;

    /**
     * Pull requests merged into a repository between two dates (inclusive, YYYY-MM-DD), oldest first.
     *
     * @throws \RuntimeException when GitHub rejects the request
     */
    public function fetchMergedPullRequests(string $owner, string $repo, string $since, string $until): array
    {
        $pulls = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept' => 'application/vnd.github+json',
            ])->get("{$this->baseUrl}/search/issues", [
                'q' => "repo:{$owner}/{$repo} is:pr is:merged merged:{$since}..{$until}",
                'sort' => 'created',
                'order' => 'asc',
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if ($response->failed()) {
                Log::warning("GitHub API: merged PR search failed for {$owner}/{$repo}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("GitHub could not list pull requests for {$owner}/{$repo} (HTTP {$response->status()}).");
            }

            $items = $response->json('items') ?? [];
            foreach ($items as $item) {
                $pulls[] = [
                    'number' => (int) $item['number'],
                    'title' => trim((string) ($item['title'] ?? '')),
                    'author' => $item['user']['login'] ?? 'unknown',
                    'merged_at' => $item['pull_request']['merged_at'] ?? ($item['closed_at'] ?? ''),
                    'url' => $item['html_url'] ?? '',
                    'repo' => "{$owner}/{$repo}",
                ];
            }

            $page++;
        } while (count($items) === $perPage && count($pulls) < self::ACTIVITY_LIMIT);

        usort($pulls, fn ($a, $b) => strcmp($a['merged_at'], $b['merged_at']));

        return $pulls;
    }

    /**
     * Billable GitHub work for a client between two dates (inclusive, YYYY-MM-DD): merged pull
     * requests or commits across the client's active linked repositories, each with a ready-made
     * invoice line description. Merge commits are left out of the commit list.
     *
     * @param  'merged_pull_requests'|'commits'  $kind
     * @param  ?string  $repo  limit to one linked repository ("owner/name" or just "name")
     * @return array{repos: string[], items: array<int, array{kind: string, repo: string, ref: string, title: string, author: string, date: string, url: string, description: string}>, truncated: bool}
     *
     * @throws \RuntimeException when GitHub is not configured or a request fails
     */
    public function activityForClient(int $clientId, string $kind, string $since, string $until, ?string $repo = null): array
    {
        $repos = ClientRepository::where('client_id', $clientId)->where('is_active', true)->orderBy('owner')->orderBy('repo_name')->get();

        if ($repo !== null && trim($repo) !== '') {
            $wanted = strtolower(trim($repo));
            $repos = $repos->filter(fn ($r) => strtolower($r->full_name) === $wanted || strtolower($r->repo_name) === $wanted)->values();
        }

        $names = $repos->map(fn ($r) => $r->full_name)->all();
        $multiRepo = count($names) > 1;
        $items = [];

        foreach ($repos as $r) {
            if ($kind === 'commits') {
                $commits = $this->fetchCommits($r->owner, $r->repo_name, "{$since}T00:00:00Z", "{$until}T23:59:59Z", $r->default_branch);
                foreach (array_reverse($commits) as $commit) {
                    $title = trim(strtok((string) $commit['message'], "\n") ?: '');
                    if ($title === '' || preg_match('/^Merge (pull request|branch|remote-tracking branch)\b/i', $title)) {
                        continue;
                    }
                    $items[] = [
                        'kind' => 'commit',
                        'repo' => $r->full_name,
                        'ref' => $commit['sha'],
                        'title' => $title,
                        'author' => $commit['author_name'],
                        'date' => substr((string) $commit['date'], 0, 10),
                        'url' => "https://github.com/{$r->full_name}/commit/{$commit['sha']}",
                        'description' => $title . ' (commit ' . $commit['sha'] . ($multiRepo ? ', ' . $r->repo_name : '') . ')',
                    ];
                }
            } else {
                foreach ($this->fetchMergedPullRequests($r->owner, $r->repo_name, $since, $until) as $pull) {
                    $items[] = [
                        'kind' => 'pull_request',
                        'repo' => $r->full_name,
                        'ref' => '#' . $pull['number'],
                        'title' => $pull['title'],
                        'author' => $pull['author'],
                        'date' => substr((string) $pull['merged_at'], 0, 10),
                        'url' => $pull['url'],
                        'description' => 'PR #' . $pull['number'] . ($multiRepo ? ' (' . $r->repo_name . ')' : '') . ': ' . $pull['title'],
                    ];
                }
            }
        }

        usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $truncated = count($items) > self::ACTIVITY_LIMIT;

        return ['repos' => $names, 'items' => array_slice($items, 0, self::ACTIVITY_LIMIT), 'truncated' => $truncated];
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.github.token'));
    }

    public function validateRepo(string $owner, string $repo): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token(),
            'Accept' => 'application/vnd.github+json',
        ])->head("{$this->baseUrl}/repos/{$owner}/{$repo}");

        return $response->successful();
    }

    /**
     * Fetch all repos accessible by the token (user repos + org repos).
     * Returns a flat list sorted by full_name.
     */
    public function fetchAccessibleRepos(): array
    {
        $repos = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept' => 'application/vnd.github+json',
            ])->get("{$this->baseUrl}/user/repos", [
                'per_page' => $perPage,
                'page' => $page,
                'sort' => 'full_name',
                'direction' => 'asc',
                'affiliation' => 'owner,collaborator,organization_member',
            ]);

            if ($response->failed()) {
                Log::warning('GitHub API: failed to fetch repos', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                break;
            }

            $items = $response->json();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $repos[] = [
                    'owner' => $item['owner']['login'],
                    'name' => $item['name'],
                    'full_name' => $item['full_name'],
                    'default_branch' => $item['default_branch'] ?? 'main',
                    'private' => $item['private'],
                ];
            }

            $page++;
        } while (count($items) === $perPage);

        return $repos;
    }

    /**
     * Fetch branches for a specific repo.
     */
    public function fetchBranches(string $owner, string $repo): array
    {
        $branches = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept' => 'application/vnd.github+json',
            ])->get("{$this->baseUrl}/repos/{$owner}/{$repo}/branches", [
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if ($response->failed()) {
                break;
            }

            $items = $response->json();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $branches[] = $item['name'];
            }

            $page++;
        } while (count($items) === $perPage);

        return $branches;
    }
}
