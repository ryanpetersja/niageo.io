<?php

namespace App\Http\Controllers;

use App\Services\AiUsageService;
use App\Services\Voice\VoiceCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoiceCommandController extends Controller
{
    public function __construct(private VoiceCommandService $voice, private AiUsageService $usage) {}

    /**
     * Interpret a spoken or typed command for the screen the user is on.
     */
    public function interpret(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transcript' => 'required|string|max:1000',
            'page' => 'required|array',
            'page.name' => 'required|string|max:64',
            'page.context' => 'nullable|array',
            'page.today' => 'nullable|date_format:Y-m-d',
            'history' => 'nullable|array|max:' . VoiceCommandService::MAX_HISTORY,
            'history.*.role' => 'required|in:user,assistant',
            'history.*.text' => 'required|string|max:2000',
            'task' => 'nullable|array',
            'task.original' => 'required_with:task|string|max:1000',
            'task.remaining' => 'required_with:task|string|max:1000',
            'task.step' => 'nullable|integer|min:1|max:20',
            'task.done' => 'nullable|array|max:12',
            'task.done.*' => 'string|max:300',
        ]);

        if (empty(config('services.anthropic.api_key'))) {
            return response()->json([
                'reply' => 'Voice commands are not configured yet. Add the Anthropic API key to the server settings.',
                'actions' => [],
                'confirm' => null,
            ], 503);
        }

        if ($this->usage->isBlocked('voice')) {
            return response()->json([
                'reply' => 'The voice assistant is paused because the monthly AI budget has been reached. An admin can raise it under Settings, AI Usage.',
                'actions' => [],
                'confirm' => null,
            ], 503);
        }

        try {
            $result = $this->voice->interpret(
                $validated['transcript'],
                $validated['page']['name'],
                $validated['page']['context'] ?? [],
                $validated['history'] ?? [],
                $validated['page']['today'] ?? null,
                $validated['task'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('Voice command failed', [
                'page' => $validated['page']['name'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'reply' => 'Sorry, the assistant is unavailable right now. Please try again.',
                'actions' => [],
                'confirm' => null,
            ], 503);
        }

        return response()->json($result);
    }
}
