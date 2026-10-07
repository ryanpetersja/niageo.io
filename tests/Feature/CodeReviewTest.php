<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CodeReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodeReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    private $repo;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.anthropic.api_key', 'sk-test');
        Config::set('services.github.token', 'gh-test');
        $this->user = User::factory()->create();
        $this->client = Client::create(['company_name' => 'Campus Elite', 'billing_terms' => 'net_30', 'is_active' => true]);
        $this->repo = $this->client->repositories()->create(['owner' => 'acme', 'repo_name' => 'portal', 'default_branch' => 'main']);
    }

    private function pull(int $number, string $title, string $state = 'open', ?string $mergedAt = null): array
    {
        return [
            'number' => $number, 'title' => $title, 'state' => $state, 'draft' => false, 'merged_at' => $mergedAt,
            'user' => ['login' => 'alice'], 'base' => ['ref' => 'main'], 'head' => ['ref' => 'feature/' . $number],
            'created_at' => '2026-10-01T10:00:00Z', 'updated_at' => '2026-10-06T10:00:00Z', 'html_url' => "https://github.com/acme/portal/pull/{$number}",
            'body' => 'Adds the thing.', 'mergeable_state' => 'clean', 'commits' => 3, 'changed_files' => 2, 'additions' => 40, 'deletions' => 5,
        ];
    }

    private function claudeReview(): array
    {
        $json = json_encode([
            'summary' => 'Two PRs: merge #12, hold #13 until the env var exists.',
            'pull_requests' => [
                ['number' => 12, 'recommendation' => 'merge', 'headline' => 'SSO login', 'functionality' => 'Adds SsoController and a users.sso_id column.', 'reasons' => ['Complete and tested'], 'risks' => []],
                ['number' => 13, 'recommendation' => 'hold', 'headline' => 'Stripe webhooks', 'functionality' => 'Adds a webhook route.', 'reasons' => ['Needs STRIPE_WEBHOOK_SECRET'], 'risks' => ['Webhook 500s until the secret is set']],
            ],
            'merge_order' => ['#12 first'],
            'deployment_steps' => ['Add STRIPE_WEBHOOK_SECRET to .env', 'Run php artisan queue:restart'],
            'database_updates' => ['users: add nullable sso_id (additive, safe on live data)'],
            'post_deploy_checks' => ['Log in with SSO'],
        ]);

        return ['id' => 'msg', 'type' => 'message', 'role' => 'assistant', 'content' => [['type' => 'text', 'text' => "```json\n{$json}\n```"]], 'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 5000, 'output_tokens' => 400]];
    }

    public function test_pull_picker_lists_open_and_recent_pull_requests(): void
    {
        Http::fake([
            'api.github.com/repos/acme/portal/pulls?*' => fn (Request $request) => Http::response(
                str_contains($request->url(), 'state=open')
                    ? [$this->pull(13, 'Stripe webhooks')]
                    : [
                        $this->pull(12, 'SSO login', 'closed', '2026-10-05T10:00:00Z'),
                        array_merge($this->pull(3, 'Old thing', 'closed'), ['updated_at' => '2025-01-01T00:00:00Z']),
                        $this->pull(2, 'Older thing', 'closed'), // after a stale one: must not be reached
                    ]
            ),
        ]);

        $this->actingAs($this->user)
            ->getJson(route('code-reviews.pulls', ['client' => $this->client, 'repository' => $this->repo]))
            ->assertOk()
            ->assertJsonCount(2, 'pulls')
            ->assertJsonPath('pulls.0.number', 13)
            ->assertJsonPath('pulls.0.state', 'open')
            ->assertJsonPath('pulls.1.state', 'merged');
    }

    public function test_generating_a_review_fetches_the_diffs_and_stores_the_sections(): void
    {
        Http::fake([
            'api.github.com/repos/acme/portal/pulls/12/files*' => Http::response([
                ['filename' => 'database/migrations/2026_10_01_add_sso_id_to_users.php', 'status' => 'added', 'additions' => 20, 'deletions' => 0, 'patch' => '+Schema::table(users)...'],
                ['filename' => 'app/Http/Controllers/SsoController.php', 'status' => 'added', 'additions' => 20, 'deletions' => 5, 'patch' => '+class SsoController {}'],
            ]),
            'api.github.com/repos/acme/portal/pulls/13/files*' => Http::response([
                ['filename' => 'routes/web.php', 'status' => 'modified', 'additions' => 2, 'deletions' => 0, 'patch' => '+Route::post(webhook)'],
            ]),
            'api.github.com/repos/acme/portal/pulls/12' => Http::response($this->pull(12, 'SSO login')),
            'api.github.com/repos/acme/portal/pulls/13' => Http::response($this->pull(13, 'Stripe webhooks')),
            'api.anthropic.com/*' => Http::response($this->claudeReview()),
        ]);

        $response = $this->actingAs($this->user)->post(route('code-reviews.store', $this->client), [
            'client_repository_id' => $this->repo->id,
            'pull_numbers' => [12, 13],
            'deploy_script' => "git pull\ncomposer install\nphp artisan migrate --force",
        ]);

        $review = CodeReview::first();
        $response->assertRedirect(route('code-reviews.show', $review));

        $this->assertSame('2 pull requests (#12, #13)', $review->title);
        $this->assertSame('merge', $review->verdicts()[12]['recommendation']);
        $this->assertSame('hold', $review->verdicts()[13]['recommendation']);
        $this->assertSame(['Add STRIPE_WEBHOOK_SECRET to .env', 'Run php artisan queue:restart'], $review->sections['deployment_steps']);
        $this->assertSame(['database/migrations/2026_10_01_add_sso_id_to_users.php'], $review->pull_requests[0]['deploy_sensitive_files']);
        $this->assertSame("git pull\ncomposer install\nphp artisan migrate --force", $this->repo->fresh()->deploy_script);

        // Claude saw the deploy script and both diffs, with the migration before the controller.
        $claude = collect(Http::recorded())->map(fn ($p) => $p[0])->first(fn (Request $r) => str_contains($r->url(), 'anthropic'));
        $prompt = $claude['messages'][0]['content'];
        $this->assertStringContainsString('php artisan migrate --force', $prompt);
        $this->assertStringContainsString('=== PR #12: SSO login', $prompt);
        $this->assertStringContainsString('+Route::post(webhook)', $prompt);
        $this->assertLessThan(strpos($prompt, 'SsoController.php'), strpos($prompt, 'add_sso_id_to_users.php'));
        $this->assertSame('code_review', collect(\App\Models\AiUsageLog::all())->last()?->feature);

        $this->actingAs($this->user)->get(route('code-reviews.show', $review))
            ->assertOk()
            ->assertSee('Add STRIPE_WEBHOOK_SECRET to .env')
            ->assertSee('users: add nullable sso_id')
            ->assertSee('Hold');
    }

    public function test_github_failure_is_reported_without_saving(): void
    {
        Http::fake(['api.github.com/*' => Http::response(['message' => 'Not Found'], 404), 'api.anthropic.com/*' => Http::response([], 500)]);

        $this->actingAs($this->user)->from(route('code-reviews.create', $this->client))
            ->post(route('code-reviews.store', $this->client), ['client_repository_id' => $this->repo->id, 'pull_numbers' => [12]])
            ->assertRedirect(route('code-reviews.create', $this->client))
            ->assertSessionHas('error');

        $this->assertSame(0, CodeReview::count());
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'anthropic'));
    }

    public function test_a_linked_repository_branch_can_be_changed(): void
    {
        $this->actingAs($this->user)
            ->putJson(route('repositories.update', ['client' => $this->client, 'repository' => $this->repo]), ['default_branch' => 'production'])
            ->assertOk()
            ->assertJsonPath('repository.default_branch', 'production');
        $this->assertSame('production', $this->repo->fresh()->default_branch);

        $other = Client::create(['company_name' => 'Other', 'billing_terms' => 'net_30', 'is_active' => true]);
        $this->actingAs($this->user)
            ->putJson(route('repositories.update', ['client' => $other, 'repository' => $this->repo]), ['default_branch' => 'x'])
            ->assertForbidden();
    }

    public function test_review_pages_render(): void
    {
        $this->actingAs($this->user)->get(route('code-reviews.create', $this->client))->assertOk()->assertSee('acme/portal');
        $this->actingAs($this->user)->get(route('code-reviews.index'))->assertOk();
        $this->actingAs($this->user)->get(route('clients.show', $this->client))->assertOk()->assertSee('Review PRs');
    }
}
