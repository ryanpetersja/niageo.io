<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    public function distillFeedback(array $feedbackTexts, array $currentRules): array
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model', 'claude-sonnet-4-5-20250929');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured.');
        }

        $currentRulesList = !empty($currentRules)
            ? collect($currentRules)->map(fn ($r, $i) => ($i + 1) . ". {$r}")->implode("\n")
            : '(none)';

        $feedbackList = collect($feedbackTexts)->map(fn ($f, $i) => ($i + 1) . ". {$f}")->implode("\n");

        $systemPrompt = <<<PROMPT
You are maintaining a set of preference rules that guide how AI-generated report summaries are written.

Your job:
1. Read the current rules and new user feedback
2. Merge new feedback into the rules — add, update, or replace as needed
3. If new feedback contradicts an existing rule, the NEW feedback wins (replace the old rule)
4. Deduplicate — don't have two rules saying the same thing
5. Keep rules concise, actionable, and specific
6. Cap at 15 rules maximum — if over 15, merge or drop the least important
7. Always respond with valid JSON only — no markdown fences, no explanation

Each rule should be a clear instruction that can be appended to a report generation prompt.
PROMPT;

        $userPrompt = <<<PROMPT
Current rules:
{$currentRulesList}

New feedback to incorporate:
{$feedbackList}

Return the updated rules as a JSON array of strings:
["rule 1", "rule 2", ...]
PROMPT;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 2048,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Claude API failed (distill feedback)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to distill feedback into preferences.');
        }

        $body = $response->json();
        $text = $body['content'][0]['text'] ?? '';

        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $rules = json_decode($text, true);

        if (!is_array($rules)) {
            Log::error('Claude returned invalid JSON (distill feedback)', ['text' => $text]);
            throw new \RuntimeException('AI returned an invalid response while distilling feedback.');
        }

        // Ensure all items are strings and cap at 15
        $rules = collect($rules)->filter(fn ($r) => is_string($r) && trim($r) !== '')->values()->take(15)->all();

        return $rules;
    }

    public function summarizeCommits(array $commits, string $clientName, array $preferences = []): array
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

        if (!empty($preferences)) {
            $prefList = collect($preferences)->map(fn ($p) => "- {$p}")->implode("\n");
            $systemPrompt .= <<<PROMPT


Additional User Preferences (follow these as supplementary guidance):
{$prefList}
PROMPT;
        }

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

    public function reviseSummary(array $currentSummary, string $feedback, string $clientName, ?string $category = null, ?int $itemIndex = null, ?string $itemText = null): array
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model', 'claude-sonnet-4-5-20250929');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured.');
        }

        $summaryJson = json_encode($currentSummary, JSON_PRETTY_PRINT);

        $systemPrompt = <<<PROMPT
You are revising a client-facing development/maintenance report summary for a client named "{$clientName}".

The summary has 5 categories: features, bugs, improvements, security, infrastructure.
Each category contains an array of plain-language bullet points.

Your job:
1. Read the current summary and the user's feedback
2. Apply the feedback by revising the summary — you may reword, recategorize, remove, split, or merge items
3. Keep the same plain-language, business-friendly style
4. Return the full revised summary as valid JSON with all 5 keys
5. Always respond with valid JSON only — no markdown fences, no explanation

Do NOT add items that weren't in the original unless the feedback explicitly requests it.
PROMPT;

        $userPrompt = "Current summary:\n{$summaryJson}\n\n";

        if ($category !== null && $itemIndex !== null && $itemText !== null) {
            $userPrompt .= "The user is giving feedback about a specific item:\n";
            $userPrompt .= "- Category: {$category}\n";
            $userPrompt .= "- Item index: {$itemIndex}\n";
            $userPrompt .= "- Item text: \"{$itemText}\"\n\n";
        }

        $userPrompt .= "User feedback:\n{$feedback}\n\n";
        $userPrompt .= "Return the revised summary as JSON:\n{\"features\":[...],\"bugs\":[...],\"improvements\":[...],\"security\":[...],\"infrastructure\":[...]}";

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
            Log::error('Claude API failed (revise summary)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to generate revised summary. Please try again.');
        }

        $body = $response->json();
        $text = $body['content'][0]['text'] ?? '';

        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $summary = json_decode($text, true);

        if (!is_array($summary)) {
            Log::error('Claude returned invalid JSON (revise summary)', ['text' => $text]);
            throw new \RuntimeException('AI returned an invalid response. Please try again.');
        }

        $defaults = ['features' => [], 'bugs' => [], 'improvements' => [], 'security' => [], 'infrastructure' => []];
        return array_merge($defaults, $summary);
    }

    public function summarizeServerActivity(array $commands, string $clientName, array $preferences = []): array
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

        if (!empty($preferences)) {
            $prefList = collect($preferences)->map(fn ($p) => "- {$p}")->implode("\n");
            $systemPrompt .= <<<PROMPT


Additional User Preferences (follow these as supplementary guidance):
{$prefList}
PROMPT;
        }

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
