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
        return config('services.github.token', '');
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
