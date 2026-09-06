<?php

namespace Tests\Feature\Voice;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceCommandTest extends TestCase
{
    private function user(): User
    {
        $user = new User(['name' => 'Voice Tester', 'email' => 'voice@example.test', 'role' => 'admin']);
        $user->id = 999;
        $user->exists = true;

        return $user;
    }

    private function claudeResponse(array $content, string $stopReason = 'tool_use'): array
    {
        return [
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5',
            'content' => $content,
            'stop_reason' => $stopReason,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ];
    }

    private function indexContext(): array
    {
        return [
            'filters' => ['search' => '', 'status' => '', 'client_id' => ''],
            'clients' => [['id' => 2, 'name' => 'Acme Corp'], ['id' => 3, 'name' => 'Globex Inc']],
            'invoices' => [
                ['number' => 'INV-PREV-1003', 'client' => 'Acme Corp', 'status' => 'overdue', 'total' => 6008, 'balance_due' => 6008, 'due_date' => '2026-08-17', 'title' => ''],
            ],
            'pagination' => ['from' => 1, 'to' => 1, 'total' => 1, 'current_page' => 1, 'last_page' => 1],
        ];
    }

    public function test_guests_cannot_use_the_endpoint(): void
    {
        $this->postJson(route('voice.interpret'), ['transcript' => 'hi', 'page' => ['name' => 'invoices.index']])
            ->assertUnauthorized();
    }

    public function test_missing_api_key_returns_a_clear_message(): void
    {
        Config::set('services.anthropic.api_key', null);
        Http::fake();

        $this->actingAs($this->user())
            ->postJson(route('voice.interpret'), ['transcript' => 'show overdue invoices', 'page' => ['name' => 'invoices.index']])
            ->assertStatus(503)
            ->assertJsonPath('actions', [])
            ->assertJsonFragment(['reply' => 'Voice commands are not configured yet. Add the Anthropic API key to the server settings.']);

        Http::assertNothingSent();
    }

    public function test_tool_calls_become_actions_and_screen_context_is_sent_to_claude(): void
    {
        Config::set('services.anthropic.api_key', 'sk-test');
        Config::set('services.anthropic.voice_model', 'claude-opus-5');
        Config::set('services.anthropic.voice_effort', 'low');

        Http::fake([
            'api.anthropic.com/*' => Http::response($this->claudeResponse([
                ['type' => 'text', 'text' => 'Filtered to overdue invoices for Acme.'],
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'set_filters', 'input' => ['status' => 'overdue', 'client_id' => '2']],
                ['type' => 'tool_use', 'id' => 'toolu_2', 'name' => 'not_a_tool', 'input' => []],
            ])),
        ]);

        $response = $this->actingAs($this->user())->postJson(route('voice.interpret'), [
            'transcript' => 'show me overdue invoices for ackme',
            'page' => ['name' => 'invoices.index', 'context' => $this->indexContext(), 'today' => '2026-09-06'],
            'history' => [
                ['role' => 'user', 'text' => 'hello'],
                ['role' => 'assistant', 'text' => 'Hi! What would you like to do?'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'Filtered to overdue invoices for Acme.')
            ->assertJsonPath('page', 'invoices.index')
            ->assertJsonPath('confirm', null)
            ->assertJsonCount(1, 'actions')
            ->assertJsonPath('actions.0.name', 'set_filters')
            ->assertJsonPath('actions.0.input.status', 'overdue')
            ->assertJsonPath('actions.0.input.client_id', 2)
            ->assertJsonPath('usage.input', 10)
            ->assertJsonPath('usage.output', 5)
            ->assertJsonPath('usage.cache_read', 0);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $toolNames = array_column($body['tools'], 'name');
            $lastMessage = end($body['messages']);

            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'sk-test')
                && $body['model'] === 'claude-opus-5'
                && $body['output_config']['effort'] === 'low'
                && $body['system'][0]['cache_control']['type'] === 'ephemeral'
                && str_contains($body['system'][0]['text'], 'Invoice list (invoices.index): set_filters, open_invoice')
                && str_contains($body['system'][0]['text'], 'Invoice editor (invoices.form):')
                && in_array('set_filters', $toolNames, true)
                && in_array('open_invoice', $toolNames, true)
                && in_array('navigate', $toolNames, true)
                && in_array('continue_task', $toolNames, true)
                && count($body['messages']) === 3
                && $body['messages'][0]['role'] === 'user'
                && str_contains($lastMessage['content'], 'Today: Sunday, 6 September 2026 (2026-09-06)')
                && str_contains($lastMessage['content'], 'Acme Corp')
                && str_contains($lastMessage['content'], 'INV-PREV-1003')
                && str_contains($lastMessage['content'], 'show me overdue invoices for ackme');
        });
    }

    public function test_irreversible_actions_come_back_with_a_confirmation_prompt(): void
    {
        Config::set('services.anthropic.api_key', 'sk-test');

        Http::fake([
            'api.anthropic.com/*' => Http::response($this->claudeResponse([
                ['type' => 'text', 'text' => 'Recorded the payment.'],
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'record_payment', 'input' => ['amount' => 500, 'payment_method' => 'Bank_Transfer']],
            ])),
        ]);

        $response = $this->actingAs($this->user())->postJson(route('voice.interpret'), [
            'transcript' => 'the client paid five hundred by bank transfer',
            'page' => ['name' => 'invoices.show', 'context' => [
                'invoice' => ['number' => 'INV-PREV-1005', 'status' => 'sent', 'balance_due' => 1000, 'client' => 'Globex Inc', 'client_id' => 3],
                'line_items' => [],
                'payments' => [],
                'valid_transitions' => ['draft', 'paid', 'overdue', 'cancelled'],
                'can_edit' => false,
                'can_delete' => false,
                'can_record_payment' => true,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('actions.0.name', 'record_payment')
            ->assertJsonPath('actions.0.input.amount', 500)
            ->assertJsonPath('actions.0.input.payment_method', 'bank_transfer')
            ->assertJsonPath('confirm.prompt', 'Record a payment of $500.00 by bank transfer dated today on INV-PREV-1005? Say yes to confirm.');
    }

    public function test_unknown_pages_only_get_global_tools_and_questions_need_no_actions(): void
    {
        Config::set('services.anthropic.api_key', 'sk-test');

        Http::fake([
            'api.anthropic.com/*' => Http::response($this->claudeResponse([
                ['type' => 'text', 'text' => 'This screen has no voice actions yet, but I can take you to invoices.'],
            ], 'end_turn')),
        ]);

        $this->actingAs($this->user())
            ->postJson(route('voice.interpret'), ['transcript' => 'what can you do here', 'page' => ['name' => 'reports.index']])
            ->assertOk()
            ->assertJsonPath('actions', [])
            ->assertJsonPath('page', 'reports.index');

        Http::assertSent(function ($request) {
            $toolNames = array_column($request->data()['tools'], 'name');
            sort($toolNames);

            return $toolNames === ['continue_task', 'navigate', 'show_help', 'stop_listening'];
        });
    }

    public function test_multi_step_tasks_are_continued_on_the_next_screen(): void
    {
        Config::set('services.anthropic.api_key', 'sk-test');

        Http::fake([
            'api.anthropic.com/*' => Http::response($this->claudeResponse([
                ['type' => 'text', 'text' => 'Reduced every price by 15 percent and saved; heading back to the list.'],
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'adjust_prices', 'input' => ['percent' => -15, 'lines' => ['1', 2]]],
                ['type' => 'tool_use', 'id' => 'toolu_2', 'name' => 'save_invoice', 'input' => []],
                ['type' => 'tool_use', 'id' => 'toolu_3', 'name' => 'continue_task', 'input' => ['remaining' => 'go to the invoices list and filter by client Acme Corp', 'done' => 'Cut prices by 15% and saved']],
            ])),
        ]);

        $response = $this->actingAs($this->user())->postJson(route('voice.interpret'), [
            'transcript' => 'open that invoice, cut every price by fifteen percent, save it, then go back and show only acme',
            'page' => ['name' => 'invoices.form', 'context' => ['mode' => 'edit', 'invoice_number' => 'INV-1', 'line_items' => [['line' => 1, 'description' => 'Hosting', 'quantity' => 1, 'unit_price' => 100]]]],
            'task' => ['original' => 'open that invoice, cut every price by fifteen percent, save it, then go back and show only acme', 'remaining' => 'reduce every line by 15%, save, then filter the list by Acme', 'step' => 2, 'done' => ['Opened invoice INV-1']],
        ]);

        $response->assertOk()
            ->assertJsonPath('actions.0.name', 'adjust_prices')
            ->assertJsonPath('actions.0.input.percent', -15)
            ->assertJsonPath('actions.0.input.lines', [1, 2])
            ->assertJsonPath('actions.1.name', 'save_invoice')
            ->assertJsonPath('actions.2.name', 'continue_task')
            ->assertJsonPath('actions.2.input.remaining', 'go to the invoices list and filter by client Acme Corp');

        Http::assertSent(function ($request) {
            $content = end($request->data()['messages'])['content'];

            return str_contains($content, 'CONTINUING A MULTI-STEP REQUEST (screen 3)')
                && str_contains($content, 'Done so far: 1. Opened invoice INV-1')
                && str_contains($content, 'Your note of what remains: "reduce every line by 15%, save, then filter the list by Acme"')
                && ! str_contains($content, 'USER SAID');
        });
    }

    public function test_api_failures_are_reported_gracefully(): void
    {
        Config::set('services.anthropic.api_key', 'sk-test');
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'overloaded']], 529)]);

        $this->actingAs($this->user())
            ->postJson(route('voice.interpret'), ['transcript' => 'save the invoice', 'page' => ['name' => 'invoices.form']])
            ->assertStatus(503)
            ->assertJsonPath('actions', [])
            ->assertJsonPath('reply', 'Sorry, the assistant is unavailable right now. Please try again.');
    }

    public function test_transcript_is_required_and_capped(): void
    {
        Config::set('services.anthropic.api_key', 'sk-test');
        Http::fake();

        $this->actingAs($this->user())
            ->postJson(route('voice.interpret'), ['transcript' => '', 'page' => ['name' => 'invoices.index']])
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
