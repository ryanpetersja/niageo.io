<?php

namespace Tests\Feature;

use App\Mail\AiBudgetAlert;
use App\Models\AiBudget;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\ClaudeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AiUsageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        Config::set('services.anthropic.api_key', 'sk-test');
        Config::set('services.anthropic.voice_model', 'claude-opus-5');
    }

    private function fakeClaude(array $usage, string $model = 'claude-opus-5'): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => $model,
            'content' => [['type' => 'text', 'text' => 'Done.']],
            'stop_reason' => 'end_turn',
            'usage' => $usage,
        ])]);
    }

    public function test_every_claude_call_is_recorded_with_its_estimated_cost(): void
    {
        $this->fakeClaude(['input_tokens' => 900, 'cache_read_input_tokens' => 2900, 'cache_creation_input_tokens' => 0, 'output_tokens' => 120]);

        $this->actingAs($this->admin)
            ->postJson(route('voice.interpret'), ['transcript' => 'what is on screen', 'page' => ['name' => 'invoices.index']])
            ->assertOk()
            ->assertJsonPath('usage.input', 900)
            ->assertJsonPath('budget', null);

        $log = AiUsageLog::firstOrFail();
        $this->assertSame('voice', $log->feature);
        $this->assertSame('claude-opus-5', $log->model);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(2900, $log->cache_read_tokens);
        $this->assertEqualsWithDelta(0.00895, $log->estimated_cost, 0.000001);
        $this->assertNotNull($log->duration_ms);

        // Calls made through the other features are recorded under their own key.
        app(ClaudeService::class)->send(['model' => 'claude-sonnet-4-5-20250929', 'max_tokens' => 10, 'messages' => []], 30, 'report_summary');
        $this->assertSame('report_summary', AiUsageLog::latest('id')->first()->feature);
        $this->assertSame(2, AiUsageLog::count());
    }

    public function test_dashboard_is_admin_only_and_shows_totals(): void
    {
        AiUsageLog::create(['feature' => 'voice', 'model' => 'claude-opus-5', 'input_tokens' => 1000, 'output_tokens' => 100, 'estimated_cost' => 0.0075, 'user_id' => $this->admin->id]);
        AiUsageLog::create(['feature' => 'scope_items', 'model' => 'claude-sonnet-4-5-20250929', 'input_tokens' => 5000, 'output_tokens' => 2000, 'estimated_cost' => 0.045]);

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('settings.ai-usage'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('settings.ai-usage'))
            ->assertOk()
            ->assertSee('Voice assistant')
            ->assertSee('Scope builder — items')
            ->assertSee('$0.05')
            ->assertSee('System (scheduled)');
    }

    public function test_budget_settings_can_be_saved_and_block_the_voice_assistant_when_exceeded(): void
    {
        $this->actingAs($this->admin)->put(route('settings.ai-usage.budget'), [
            'monthly_budget' => 10, 'daily_budget' => '', 'action' => 'block_voice',
            'credits_balance' => 100, 'credits_balance_at' => now()->toDateString(), 'alert_emails' => 'owner@example.test, bad-address',
        ])->assertRedirect(route('settings.ai-usage'));

        $budget = AiBudget::current();
        $this->assertEquals(10.0, $budget->monthly_budget);
        $this->assertNull($budget->daily_budget);
        $this->assertSame('block_voice', $budget->action);
        $this->assertSame(['owner@example.test'], $budget->recipients());

        // Under budget: voice works and reports the budget percentage.
        $this->fakeClaude(['input_tokens' => 1000, 'output_tokens' => 100]);
        $this->actingAs($this->admin)
            ->postJson(route('voice.interpret'), ['transcript' => 'hello', 'page' => ['name' => 'invoices.index']])
            ->assertOk()
            ->assertJsonPath('budget.limit', 10)
            ->assertJsonPath('budget.percent', 0);

        // Spend past the limit: voice is paused, other features still run.
        AiUsageLog::create(['feature' => 'report_summary', 'model' => 'claude-opus-5', 'estimated_cost' => 12]);
        $this->actingAs($this->admin)
            ->postJson(route('voice.interpret'), ['transcript' => 'hello again', 'page' => ['name' => 'invoices.index']])
            ->assertStatus(503)
            ->assertJsonFragment(['reply' => 'The voice assistant is paused because the monthly AI budget has been reached. An admin can raise it under Settings, AI Usage.']);
        app(ClaudeService::class)->send(['model' => 'claude-opus-5', 'max_tokens' => 10, 'messages' => []], 30, 'report_summary');

        $budget->update(['action' => 'block_all']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI features are paused');
        app(ClaudeService::class)->send(['model' => 'claude-opus-5', 'max_tokens' => 10, 'messages' => []], 30, 'report_summary');
    }

    public function test_alerts_are_emailed_once_per_threshold(): void
    {
        Mail::fake();
        AiBudget::current()->update(['monthly_budget' => 1, 'alert_emails' => 'owner@example.test']);

        // Each Opus call below costs $0.30 (60k input tokens), so thresholds are crossed at 2, 3 and 4 calls.
        $this->fakeClaude(['input_tokens' => 60000, 'output_tokens' => 0]);
        for ($i = 0; $i < 5; $i++) {
            app(ClaudeService::class)->send(['model' => 'claude-opus-5', 'max_tokens' => 10, 'messages' => []], 30, 'report_summary');
        }

        Mail::assertSent(AiBudgetAlert::class, 3);
        Mail::assertSent(AiBudgetAlert::class, fn ($mail) => $mail->threshold === 100 && $mail->hasTo('owner@example.test'));
        $this->assertSame([50, 80, 100], AiBudget::current()->alerts_sent[now()->format('Y-m')]);
    }

    public function test_runway_estimates_when_prepaid_credits_run_out(): void
    {
        AiBudget::current()->update(['credits_balance' => 20, 'credits_balance_at' => now()->subDays(6)->toDateString()]);
        for ($d = 0; $d < 7; $d++) {
            AiUsageLog::create(['feature' => 'voice', 'model' => 'claude-opus-5', 'estimated_cost' => 0.5, 'created_at' => now()->subDays($d)]);
        }

        $runway = app(\App\Services\AiUsageService::class)->runway();

        $this->assertEqualsWithDelta(3.5, $runway['spent_since'], 0.0001);
        $this->assertEqualsWithDelta(16.5, $runway['remaining'], 0.0001);
        $this->assertEqualsWithDelta(0.25, $runway['daily_rate'], 0.0001); // 3.5 over a 14-day window
        $this->assertSame(66, $runway['days_left']);
    }
}
