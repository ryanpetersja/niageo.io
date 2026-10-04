<?php

namespace App\Services\Voice\Support;

use App\Models\ClientRepository;
use App\Services\GitHubService;

/**
 * GitHub tools for the invoice editor: look up the selected client's merged pull requests or
 * commits (runs on the server, result goes back to the model), then bill them with one compact
 * add_github_lines call that expands into one add_line_item action per item.
 *
 * Items found during one request are numbered PR-1, PR-2… / C-1, C-2… so the model can pick them.
 */
class GitHubLookup
{
    public const FIND = 'find_github_work';
    public const ADD = 'add_github_lines';

    /** @var array<string, array> ref => item, for this request only */
    protected array $found = [];

    public function __construct(protected GitHubService $github) {}

    public static function tools(): array
    {
        return [
            Tool::make(
                self::FIND,
                'Look up work in the GitHub repositories linked to the client selected on this invoice: pull requests merged, or commits made, between two dates. This runs on the server and returns the list to you (each item has a ref like PR-1 or C-1) before you answer. Call it whenever the user refers to GitHub work for a period: pull requests, PRs, merges, commits, what was shipped, released or deployed. Then bill it with add_github_lines (or add_line_item when the user wants their own wording or grouping). Needs a client selected on the form; if none is, set the client first only when the user named one.',
                [
                    'kind' => Tool::string('What to list: merged pull requests or commits.', ['merged_pull_requests', 'commits']),
                    'since' => Tool::string('First day of the period, YYYY-MM-DD (inclusive). "In September" means the 1st of September of the most recent September up to today.'),
                    'until' => Tool::string('Last day of the period, YYYY-MM-DD (inclusive).'),
                    'repo' => Tool::string('Only this linked repository ("owner/name" or "name"); omit for all of the client\'s repositories.'),
                ],
                ['kind', 'since', 'until']
            ),
            Tool::make(
                self::ADD,
                'Add one invoice line per GitHub item that find_github_work returned earlier in this same request, using a ready-made description (for example "PR #42: Add SSO login"). Call this for "a line for each pull request merged in September" after the lookup. Use refs ["all"] for every item found, or list the refs to include (leave out ones the user excluded). Every line gets the same quantity and unit price.',
                [
                    'refs' => Tool::stringList('Refs from find_github_work (e.g. ["PR-1", "PR-3"]), or ["all"].'),
                    'quantity' => Tool::number('Quantity on each line (defaults to 1).'),
                    'unit_price' => Tool::number('Unit price in dollars on each line (defaults to 0).'),
                ],
                ['refs']
            ),
        ];
    }

    /** One line for the screen description: which repositories the selected client has linked. */
    public function screenLine(mixed $clientId): string
    {
        $id = is_numeric($clientId) ? (int) $clientId : 0;
        if ($id <= 0) {
            return 'GitHub: select a client to bill its GitHub work (find_github_work).';
        }

        try {
            $repos = ClientRepository::where('client_id', $id)->where('is_active', true)
                ->orderBy('owner')->orderBy('repo_name')->get()->map(fn ($r) => $r->full_name)->all();
        } catch (\Throwable $e) {
            return 'GitHub: repository list unavailable.';
        }

        if ($repos === []) {
            return 'GitHub repositories linked to this client: none (link them on the client page to bill GitHub work).';
        }

        return 'GitHub repositories linked to this client (use find_github_work to list their PRs or commits): ' . implode(', ', $repos) . '.';
    }

    public function find(array $input, array $context): string
    {
        $fields = is_array($context['fields'] ?? null) ? $context['fields'] : [];
        $clientId = is_numeric($fields['client_id'] ?? null) ? (int) $fields['client_id'] : 0;
        if ($clientId <= 0) {
            return 'No client is selected on the invoice, so there are no repositories to search. Ask which client, or set client_id first if the user named one.';
        }
        if (! $this->github->isConfigured()) {
            return 'GitHub is not connected: the server has no GitHub token configured. Tell the user an admin needs to set GITHUB_TOKEN.';
        }

        $since = (string) ($input['since'] ?? '');
        $until = (string) ($input['until'] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $since) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $until) || $since > $until) {
            return 'The period must be two dates in YYYY-MM-DD form with since on or before until.';
        }

        $kind = ($input['kind'] ?? '') === 'commits' ? 'commits' : 'merged_pull_requests';
        $label = $kind === 'commits' ? 'commits' : 'merged pull requests';

        try {
            $result = $this->github->activityForClient($clientId, $kind, $since, $until, $input['repo'] ?? null);
        } catch (\Throwable $e) {
            return 'The GitHub lookup failed: ' . $e->getMessage() . ' Tell the user it could not reach GitHub.';
        }

        if ($result['repos'] === []) {
            return isset($input['repo'])
                ? 'No linked repository matches "' . Screen::text($input['repo'], 80) . '" for this client.'
                : 'This client has no GitHub repositories linked. Tell the user to link them on the client page.';
        }

        $repos = implode(', ', $result['repos']);
        if ($result['items'] === []) {
            return "No {$label} in {$repos} between {$since} and {$until}.";
        }

        $prefix = $kind === 'commits' ? 'C-' : 'PR-';
        $lines = [];
        foreach ($result['items'] as $item) {
            $ref = $prefix . (count(array_filter(array_keys($this->found), fn ($k) => str_starts_with($k, $prefix))) + 1);
            $this->found[$ref] = $item;
            $lines[] = sprintf('%s: %s "%s" by %s, %s, %s → line "%s"', $ref, $item['ref'], Screen::text($item['title'], 160), $item['author'], $item['date'], $item['repo'], Screen::text($item['description'], 200));
        }

        return sprintf('Found %d %s in %s between %s and %s%s:', count($lines), $label, $repos, $since, $until, $result['truncated'] ? ' (only the first ' . GitHubService::ACTIVITY_LIMIT . ' are listed)' : '')
            . "\n" . implode("\n", $lines);
    }

    /**
     * add_github_lines → one add_line_item per referenced item.
     *
     * @return array<int, array{name: string, input: array}>
     */
    public function expand(array $input): array
    {
        $refs = array_map(fn ($r) => strtoupper(trim((string) $r)), (array) ($input['refs'] ?? []));
        $items = in_array('ALL', $refs, true)
            ? $this->found
            : array_intersect_key($this->found, array_flip($refs));

        $quantity = isset($input['quantity']) && (float) $input['quantity'] > 0 ? (float) $input['quantity'] : 1.0;
        $price = isset($input['unit_price']) ? max(0, (float) $input['unit_price']) : 0.0;

        return array_values(array_map(fn ($item) => [
            'name' => 'add_line_item',
            'input' => ['description' => mb_substr($item['description'], 0, 255), 'quantity' => $quantity, 'unit_price' => $price],
        ], $items));
    }
}
