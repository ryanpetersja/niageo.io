<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    public function summarizeCommits(array $commits, string $clientName): array
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model', 'claude-sonnet-4-5-20250929');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured.');
        }

        $commitList = collect($commits)->map(function ($c) {
            $msg = strtok($c['message'], "\n");
            return "- [{$c['sha']}] ({$c['repo']}) {$msg}";
        })->implode("\n");

        $systemPrompt = <<<PROMPT
You are a business communications expert writing a development progress report for a client named "{$clientName}".

Your audience is a NON-TECHNICAL business stakeholder who cares about outcomes, not code.

Rules:
- NEVER use technical jargon (no "config", "API", "routes", "middleware", "refactor", "exception handling", "components", etc.)
- Every bullet point MUST clearly state the BENEFIT to the business or end users
- Use the format: "[What was done] — [why this matters / the benefit]"
- Write in plain English that anyone can understand
- Be specific about what improved from the user's or business's perspective
- Group related commits into single meaningful items rather than listing each commit separately
- Always respond with valid JSON only — no markdown fences, no explanation
PROMPT;

        $userPrompt = <<<PROMPT
Analyze these development commits and produce a client-friendly summary organized into exactly 5 categories. Each item must clearly explain what was done AND the direct benefit to the client's business or users.

Categories:
- features: New capabilities that let users do something they couldn't before
- bugs: Problems that were affecting users, now resolved — explain what users were experiencing and that it's fixed
- improvements: Things that now work better, faster, or more smoothly for users
- security: Changes that better protect the business, its data, or its users
- infrastructure: Behind-the-scenes work that keeps the platform reliable and running smoothly

Format each item as: "[Plain-language description] — [clear benefit to the business or users]"

Example good items:
- "Added the ability to attach files to email broadcasts — clients can now receive important documents directly in their notifications"
- "Fixed an issue where search filters were not returning correct results — users can now reliably find the information they need"
- "Improved how the system handles time zones — all dates and times now display correctly for your region"

Example bad items (too technical):
- "Migrated admin email configuration to centralized config system"
- "Added exception handling for default mail"
- "Fixed duplicate route issues"

If a category has no relevant items, use an empty array.

Commits:
{$commitList}

Respond with JSON in this exact format:
{"features":["..."],"bugs":["..."],"improvements":["..."],"security":["..."],"infrastructure":["..."]}
PROMPT;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Claude API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to generate AI summary. Please try again.');
        }

        $body = $response->json();
        $text = $body['content'][0]['text'] ?? '';

        // Strip code fences if present
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $summary = json_decode($text, true);

        if (!is_array($summary)) {
            Log::error('Claude returned invalid JSON', ['text' => $text]);
            throw new \RuntimeException('AI returned an invalid response. Please try again.');
        }

        // Ensure all 5 keys exist
        $defaults = ['features' => [], 'bugs' => [], 'improvements' => [], 'security' => [], 'infrastructure' => []];
        return array_merge($defaults, $summary);
    }

    public function summarizeServerActivity(array $commands, string $clientName): array
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model', 'claude-sonnet-4-5-20250929');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured.');
        }

        $commandList = collect($commands)->map(function ($c) {
            $ts = $c['timestamp'] ? "[{$c['timestamp']}]" : '[no timestamp]';
            $label = $c['server_label'] ?? 'Server';
            return "- {$ts} ({$label}) {$c['command']}";
        })->implode("\n");

        $systemPrompt = <<<PROMPT
You are a business communications expert writing a server maintenance summary for a client named "{$clientName}".

Your audience is a NON-TECHNICAL business stakeholder who cares about outcomes, not technical commands.

Rules:
- NEVER use raw commands, file paths, or technical jargon
- Every bullet point MUST clearly state the BENEFIT to the business or end users
- Use the format: "[What was done] — [why this matters / the benefit]"
- Write in plain English that anyone can understand
- Be specific about what improved from the user's or business's perspective
- Group related commands into single meaningful items rather than listing each separately
- Always respond with valid JSON only — no markdown fences, no explanation
PROMPT;

        $userPrompt = <<<PROMPT
Analyze these server maintenance commands and produce a client-friendly summary organized into exactly 5 categories. These are commands run by the development team on the server hosting the client's application. Each item must clearly explain what was done AND the direct benefit.

Categories:
- features: Deployments or updates that brought new capabilities to users
- bugs: Server-side fixes that resolved issues users were experiencing
- improvements: Performance tuning, optimization, or upgrades that make things work better
- security: Security patches, certificate updates, firewall changes, access controls
- infrastructure: Server maintenance, backups, monitoring, service restarts, database maintenance

Format each item as: "[Plain-language description] — [clear benefit to the business or users]"

Example good items:
- "Deployed the latest application update to the production server — users now have access to the newest features and fixes"
- "Renewed the SSL security certificate — ensures the website remains secure and trusted by browsers"
- "Performed a database backup and optimization — protects business data and keeps the application running smoothly"

If a category has no relevant items, use an empty array.

Server commands:
{$commandList}

Respond with JSON in this exact format:
{"features":["..."],"bugs":["..."],"improvements":["..."],"security":["..."],"infrastructure":["..."]}
PROMPT;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Claude API failed (server activity)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to generate server activity summary. Please try again.');
        }

        $body = $response->json();
        $text = $body['content'][0]['text'] ?? '';

        // Strip code fences if present
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $summary = json_decode($text, true);

        if (!is_array($summary)) {
            Log::error('Claude returned invalid JSON (server activity)', ['text' => $text]);
            throw new \RuntimeException('AI returned an invalid response. Please try again.');
        }

        $defaults = ['features' => [], 'bugs' => [], 'improvements' => [], 'security' => [], 'infrastructure' => []];
        return array_merge($defaults, $summary);
    }
}
