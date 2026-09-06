<?php

namespace App\Http\Controllers;

use App\Services\Voice\VoiceCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoiceCommandController extends Controller
{
    public function __construct(private VoiceCommandService $voice) {}

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
        ]);

        if (empty(config('services.anthropic.api_key'))) {
            return response()->json([
                'reply' => 'Voice commands are not configured yet. Add the Anthropic API key to the server settings.',
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
