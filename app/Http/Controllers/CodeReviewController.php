<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientRepository;
use App\Models\CodeReview;
use App\Services\CodeReviewService;
use App\Services\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CodeReviewController extends Controller
{
    public function __construct(
        private CodeReviewService $reviews,
        private GitHubService $github,
    ) {}

    public function index()
    {
        $reviews = CodeReview::with(['client', 'repository'])->latest()->paginate(25);

        return view('code-reviews.index', compact('reviews'));
    }

    public function create(Client $client)
    {
        $repositories = $client->repositories()->where('is_active', true)->orderBy('owner')->orderBy('repo_name')->get();
        $selected = $repositories->firstWhere('id', request()->integer('repository'))?->id ?? $repositories->first()?->id;

        return view('code-reviews.create', [
            'client' => $client,
            'repositories' => $repositories,
            'selectedRepositoryId' => $selected,
            'githubConfigured' => $this->github->isConfigured(),
        ]);
    }

    /** Open and recently merged pull requests of a linked repository, for the picker. */
    public function pulls(Client $client, ClientRepository $repository)
    {
        if ($repository->client_id !== $client->id) {
            return response()->json(['message' => 'Repository does not belong to this client.'], 403);
        }
        if (! $this->github->isConfigured()) {
            return response()->json(['message' => 'GitHub is not connected. An admin needs to set GITHUB_TOKEN on the server.'], 422);
        }

        try {
            $pulls = $this->github->fetchPullRequests($repository->owner, $repository->repo_name);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['pulls' => $pulls]);
    }

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'client_repository_id' => 'required|integer',
            'pull_numbers' => 'required|array|min:1|max:15',
            'pull_numbers.*' => 'integer|min:1',
            'deploy_script' => 'nullable|string|max:10000',
        ]);

        $repository = $client->repositories()->findOrFail($validated['client_repository_id']);

        // The deploy script is kept on the repository so the next review starts from it.
        $script = trim((string) ($validated['deploy_script'] ?? ''));
        if ($script !== (string) $repository->deploy_script) {
            $repository->update(['deploy_script' => $script === '' ? null : $script]);
        }

        try {
            $review = $this->reviews->generate($repository, $validated['pull_numbers'], $request->user());
        } catch (\Throwable $e) {
            Log::error('Code review failed', ['repository' => $repository->full_name, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'The review could not be generated: ' . $e->getMessage());
        }

        return redirect()->route('code-reviews.show', $review)->with('success', 'Review generated.');
    }

    public function show(CodeReview $codeReview)
    {
        $codeReview->load(['client', 'repository', 'creator']);

        return view('code-reviews.show', ['review' => $codeReview]);
    }

    public function destroy(CodeReview $codeReview)
    {
        $client = $codeReview->client;
        $codeReview->delete();

        return redirect()->route('clients.show', $client)->with('success', 'Review deleted.');
    }
}
