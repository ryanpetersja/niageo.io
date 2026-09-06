<?php

namespace App\Services\Voice;

use App\Services\AiUsageService;
use App\Services\ClaudeService;
use Illuminate\Support\Facades\Log;

/**
 * Turns a spoken/typed command into UI actions for the current screen.
 *
 * Flow: the browser sends the transcript + a snapshot of the screen; we give Claude
 * the screen as text plus the screen's tools; every tool_use block in the answer
 * becomes an action the browser executes (exactly what the user could do by hand).
 * The text block becomes the spoken reply.
 */
class VoiceCommandService
{
    public const MAX_HISTORY = 10;

    public function __construct(
        private ClaudeService $claude,
        private VoicePageRegistry $registry,
    ) {}

    /**
     * @param  array<int, array{role: string, text: string}>  $history  previous turns, oldest first
     * @return array{reply: string, actions: array, confirm: ?array{prompt: string}, page: string, usage: array}
     */
    public function interpret(string $transcript, string $pageName, array $context = [], array $history = [], ?string $today = null, ?array $task = null): array
    {
        $page = $this->registry->resolve($pageName);
        $tools = array_merge($page->tools($context), GlobalTools::tools());

        $messages = $this->historyMessages($history);
        $messages[] = [
            'role' => 'user',
            'content' => $this->userTurn($page, $context, $transcript, $today, $task),
        ];

        $payload = [
            'model' => config('services.anthropic.voice_model', 'claude-opus-5'),
            'max_tokens' => 2048,
            'system' => [[
                'type' => 'text',
                'text' => $this->systemPrompt(),
                'cache_control' => ['type' => 'ephemeral'],
            ]],
            'tools' => $tools,
            'messages' => $messages,
        ];

        $effort = config('services.anthropic.voice_effort');
        if (is_string($effort) && $effort !== '') {
            $payload['output_config'] = ['effort' => $effort];
        }

        $body = $this->claude->send($payload, 30, 'voice');

        [$reply, $actions] = $this->parseResponse($body);
        $actions = $this->validateActions($actions, $tools);

        if ($reply === '' && $actions === []) {
            $reply = "Sorry, I couldn't work out how to do that on this screen.";
        } elseif ($reply === '') {
            $reply = 'Done.';
        }

        $prompts = [];
        foreach ($actions as $action) {
            $prompt = $page->confirmation($action['name'], $action['input'], $context);
            if ($prompt !== null && $prompt !== '') {
                $prompts[] = $prompt;
            }
        }

        $raw = is_array($body['usage'] ?? null) ? $body['usage'] : [];
        $usage = [
            'input' => (int) ($raw['input_tokens'] ?? 0),
            'output' => (int) ($raw['output_tokens'] ?? 0),
            'cache_read' => (int) ($raw['cache_read_input_tokens'] ?? 0),
            'cache_write' => (int) ($raw['cache_creation_input_tokens'] ?? 0),
        ];
        Log::info('Voice command', ['page' => $page->name(), 'model' => $payload['model'], 'actions' => array_column($actions, 'name')] + $usage);

        return [
            'reply' => $reply,
            'actions' => $actions,
            'confirm' => $prompts === [] ? null : ['prompt' => implode(' ', array_unique($prompts))],
            'page' => $page->name(),
            'usage' => $usage,
            'budget' => $this->budgetSummary(),
        ];
    }

    /** Month-to-date spend against the monthly budget, for the widget footer (null when no budget is set). */
    protected function budgetSummary(): ?array
    {
        try {
            $status = app(AiUsageService::class)->budgetStatus();
        } catch (\Throwable $e) {
            return null;
        }
        if ($status['monthly']['limit'] === null) {
            return null;
        }

        return [
            'percent' => $status['monthly']['percent'],
            'spent' => round($status['monthly']['spent'], 2),
            'limit' => $status['monthly']['limit'],
        ];
    }

    public function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the voice assistant built into NiageoOps, an internal business-operations web app (clients, invoices, reports, scopes, uptime monitoring, subscription bills). The user speaks commands while looking at a screen of the app. You translate each command into the UI actions the user would otherwise perform by hand, by calling the tools provided. Tools run in the user's browser, in the order you call them, after you answer.

How to respond:
- Call tools for everything the user asks that the CURRENT SCREEN supports. When one request has several parts, call several tools in one response, in the order they should run.
- Requests often span several screens ("open that invoice, cut every price by 15%, save it, then go back to the list and show only Acme"). Do what this screen supports, call the tool that opens the next screen, and in the same response call continue_task with a precise note of everything still to do. The app reopens you on the new screen with that note and the screen's data; keep going the same way until nothing remains. Never call continue_task when the request is complete.
- Prefer acting over asking. Act on any reasonable reading of the request; ask a question only when two readings would do materially different things (for example two line items match equally well).
- Always include ONE short spoken reply as plain text (no markdown, no lists, no quotes around values), written in the past tense as if the actions have already completed, for example: "Filtered to overdue invoices for Acme." or "Added website hosting at 45 dollars." Keep it under 20 words; it is read aloud.
- If the user only asks a question about what is on screen (totals, dates, status, what can be done), answer from the CURRENT SCREEN text without calling tools.
- If the request is ambiguous (for example two line items could match), ask one short question instead of guessing, and call no tools.
- If the current screen cannot do what was asked but another screen can, use the navigate tool to go there and say what to do next. If nothing in the app can do it, say so briefly.

Resolving what the user means:
- Speech-to-text is imperfect and the user speaks in full, unhurried sentences: transcripts may contain filler words, repetitions and false starts. Read for intent. Match client names, invoice numbers and line descriptions loosely ("ackme" = Acme Corp, "inv 1003" = INV-PREV-1003). Only use ids, invoice numbers and line numbers that appear on the CURRENT SCREEN; never invent them.
- "That invoice", "it", "the same client" refer to whatever was discussed or shown most recently in the conversation.
- "The first one", "the last line", "the overdue one for Acme" refer to positions and rows on the CURRENT SCREEN.
- Dates: the CURRENT SCREEN gives today's date. Convert relative dates ("next Friday", "in two weeks", "end of the month", "the 30th") into YYYY-MM-DD. "Net 30" means 30 days after the issue date.
- Money and quantities become numbers: "two hundred" = 200, "1.5k" = 1500, "fifteen percent" = 15.
- Follow-ups continue the conversation: "and the quantity to 3" refers to the line item just discussed.

Safety:
- Irreversible or money-related actions (deleting, recording payments, marking paid or cancelled) are confirmed by the app automatically before they run, so call the tool directly without asking for confirmation yourself.
- When the user replies "yes"/"no" and there is nothing to confirm, simply say there is nothing pending.
- Never call save_invoice unless the user clearly asked to save, create, update or finish the invoice.
PROMPT;
    }

    /**
     * Build the user turn: today's date, the screen description, then the transcript.
     */
    protected function userTurn(PageCapability $page, array $context, string $transcript, ?string $today = null, ?array $task = null): string
    {
        $transcript = trim(preg_replace('/\s+/u', ' ', $transcript) ?? $transcript);

        $text = "CURRENT SCREEN\nToday: " . Support\Screen::today($today) . "\n" . $page->screen($context);

        if ($task) {
            $step = max(1, (int) ($task['step'] ?? 1));
            $done = array_values(array_filter(array_map(fn ($d) => Support\Screen::text($d, 300), (array) ($task['done'] ?? []))));
            $text .= "\n\nCONTINUING A MULTI-STEP REQUEST (screen " . ($step + 1) . ")\n"
                . 'The user originally said: ' . Support\Screen::quoted($task['original'] ?? $transcript, 1000) . "\n"
                . 'Done so far: ' . ($done === [] ? '(nothing yet)' : implode(' ', array_map(fn ($i, $d) => ($i + 1) . ". {$d}", array_keys($done), $done))) . "\n"
                . 'Your note of what remains: ' . Support\Screen::quoted($task['remaining'] ?? '', 1000) . "\n"
                . 'Do the remaining steps this screen supports now. If more remains on another screen, open it and call continue_task again; when everything is done, do not call continue_task and say the whole request is complete.';

            return $text;
        }

        return $text . "\n\nUSER SAID (speech-to-text, may contain transcription errors): \"{$transcript}\"";
    }

    /**
     * Previous turns as plain text messages. Tool calls are summarised in the assistant text,
     * so no tool_use/tool_result blocks need replaying.
     */
    protected function historyMessages(array $history): array
    {
        $messages = [];

        foreach (array_slice(array_values($history), -self::MAX_HISTORY) as $turn) {
            if (! is_array($turn)) {
                continue;
            }
            $role = ($turn['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $text = trim((string) ($turn['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            // The conversation must start with a user message.
            if ($messages === [] && $role === 'assistant') {
                continue;
            }
            $messages[] = ['role' => $role, 'content' => mb_substr($text, 0, 1500)];
        }

        return $messages;
    }

    /**
     * @return array{0: string, 1: array}
     */
    protected function parseResponse(array $body): array
    {
        $reply = '';
        $actions = [];

        foreach ($body['content'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['type'] ?? '') === 'text') {
                $text = trim((string) ($block['text'] ?? ''));
                if ($text !== '') {
                    $reply .= ($reply === '' ? '' : ' ') . $text;
                }
            } elseif (($block['type'] ?? '') === 'tool_use') {
                $actions[] = [
                    'name' => (string) ($block['name'] ?? ''),
                    'input' => is_array($block['input'] ?? null) ? $block['input'] : [],
                ];
            }
        }

        if (($body['stop_reason'] ?? '') === 'refusal') {
            return ["I can't help with that request.", []];
        }

        // Strip markdown the model might still emit; the reply is read aloud.
        $reply = trim(preg_replace('/[*_`#>]+/', '', $reply) ?? $reply);

        return [$reply, $actions];
    }

    /**
     * Keep only actions that name a known tool and satisfy its schema (required keys,
     * enums, scalar types). Unknown properties are dropped; invalid actions are logged and skipped.
     */
    public function validateActions(array $actions, array $tools): array
    {
        $schemas = [];
        foreach ($tools as $tool) {
            $schemas[$tool['name']] = $tool['input_schema'];
        }

        $valid = [];
        foreach ($actions as $action) {
            $name = $action['name'] ?? '';
            if (! isset($schemas[$name])) {
                Log::warning('Voice command: unknown tool ignored', ['tool' => $name]);
                continue;
            }

            $schema = $schemas[$name];
            $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
            $input = [];
            $ok = true;

            foreach ($action['input'] ?? [] as $key => $value) {
                if (! isset($properties[$key])) {
                    continue;
                }
                $coerced = $this->coerce($value, $properties[$key]);
                if ($coerced === null) {
                    Log::warning('Voice command: invalid argument ignored', ['tool' => $name, 'arg' => $key, 'value' => $value]);
                    $ok = false;
                    break;
                }
                $input[$key] = $coerced;
            }

            foreach ($schema['required'] ?? [] as $required) {
                if (! array_key_exists($required, $input)) {
                    $ok = false;
                }
            }

            if ($ok) {
                $valid[] = ['name' => $name, 'input' => $input];
            } else {
                Log::warning('Voice command: action failed validation', ['tool' => $name, 'input' => $action['input'] ?? null]);
            }
        }

        return $valid;
    }

    /**
     * Coerce a tool argument to the schema type. Returns null when it cannot be used.
     */
    protected function coerce(mixed $value, array $property): mixed
    {
        if (($property['type'] ?? 'string') === 'array') {
            if (! is_array($value)) {
                return null;
            }
            $items = [];
            foreach (array_values($value) as $item) {
                $coerced = $this->coerce($item, $property['items'] ?? ['type' => 'string']);
                if ($coerced === null) {
                    return null;
                }
                $items[] = $coerced;
            }

            return $items;
        }

        if (is_array($value) || is_object($value)) {
            return null;
        }

        switch ($property['type'] ?? 'string') {
            case 'integer':
                return is_numeric($value) ? (int) $value : null;

            case 'number':
                return is_numeric($value) ? (float) $value : null;

            case 'boolean':
                if (is_bool($value)) {
                    return $value;
                }
                $normalized = strtolower(trim((string) $value));
                if (in_array($normalized, ['true', '1', 'yes'], true)) {
                    return true;
                }

                return in_array($normalized, ['false', '0', 'no', ''], true) ? false : null;

            default:
                $string = trim((string) $value);
                if (isset($property['enum'])) {
                    foreach ($property['enum'] as $option) {
                        if (strcasecmp($option, $string) === 0) {
                            return $option;
                        }
                    }

                    return null;
                }

                return mb_substr($string, 0, 1000);
        }
    }
}
