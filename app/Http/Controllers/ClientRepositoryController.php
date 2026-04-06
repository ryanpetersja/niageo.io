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
