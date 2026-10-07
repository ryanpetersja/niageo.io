<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientRepository;
use App\Services\GitHubService;
use Illuminate\Http\Request;

class ClientRepositoryController extends Controller
{
    public function __construct(
        private GitHubService $gitHubService,
    ) {}

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'owner' => 'required|string|max:255',
            'repo_name' => 'required|string|max:255',
            'default_branch' => 'nullable|string|max:255',
        ]);

        // Check for duplicate
        $exists = $client->repositories()
            ->where('owner', $validated['owner'])
            ->where('repo_name', $validated['repo_name'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This repository is already linked to this client.'], 422);
        }

        // Validate GitHub access
        if (!$this->gitHubService->validateRepo($validated['owner'], $validated['repo_name'])) {
            return response()->json(['message' => 'Unable to access this repository. Check the owner/name and your GitHub token.'], 422);
        }

        $repo = $client->repositories()->create([
            'owner' => $validated['owner'],
            'repo_name' => $validated['repo_name'],
            'default_branch' => $validated['default_branch'] ?: 'main',
        ]);

        return response()->json([
            'repository' => [
                'id' => $repo->id,
                'owner' => $repo->owner,
                'repo_name' => $repo->repo_name,
                'default_branch' => $repo->default_branch,
                'is_active' => $repo->is_active,
                'full_name' => $repo->full_name,
            ],
        ]);
    }

    /**
     * Merged pull requests or commits across the client's linked repositories for a period,
     * each with a suggested invoice line description (used by "Import from GitHub" on invoices).
     */
    public function activity(Request $request, Client $client)
    {
        $validated = $request->validate([
            'kind' => 'required|in:merged_pull_requests,commits',
            'since' => 'required|date_format:Y-m-d',
            'until' => 'required|date_format:Y-m-d|after_or_equal:since',
        ]);

        if (! $this->gitHubService->isConfigured()) {
            return response()->json(['message' => 'GitHub is not connected. An admin needs to set GITHUB_TOKEN on the server.'], 422);
        }

        try {
            $result = $this->gitHubService->activityForClient($client->id, $validated['kind'], $validated['since'], $validated['until']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        if ($result['repos'] === []) {
            return response()->json(['message' => "{$client->company_name} has no GitHub repositories linked. Link them on the client page first."], 422);
        }

        return response()->json($result);
    }

    /** Change the branch (and active flag) of a linked repository, e.g. after the old branch was deleted. */
    public function update(Request $request, Client $client, ClientRepository $repository)
    {
        if ($repository->client_id !== $client->id) {
            return response()->json(['message' => 'Repository does not belong to this client.'], 403);
        }

        $validated = $request->validate([
            'default_branch' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $repository->update($validated);

        return response()->json(['repository' => [
            'id' => $repository->id,
            'owner' => $repository->owner,
            'repo_name' => $repository->repo_name,
            'default_branch' => $repository->default_branch,
            'is_active' => $repository->is_active,
            'full_name' => $repository->full_name,
        ]]);
    }

    public function destroy(Client $client, ClientRepository $repository)
    {
        if ($repository->client_id !== $client->id) {
            return response()->json(['message' => 'Repository does not belong to this client.'], 403);
        }

        $repository->delete();

        return response()->json(['success' => true]);
    }

    public function githubRepos()
    {
        $repos = $this->gitHubService->fetchAccessibleRepos();

        return response()->json($repos);
    }

    public function githubBranches(Request $request)
    {
        $request->validate([
            'owner' => 'required|string',
            'repo' => 'required|string',
        ]);

        $branches = $this->gitHubService->fetchBranches(
            $request->input('owner'),
            $request->input('repo')
        );

        return response()->json($branches);
    }
}
