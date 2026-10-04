<?php

namespace Tests\Feature\Voice;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubInvoiceLinesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.anthropic.api_key', 'sk-test');
        Config::set('services.github.token', 'gh-test');
        $this->user = User::factory()->create();
        $this->client = Client::create(['company_name' => 'Campus Elite', 'billing_terms' => 'net_30', 'is_active' => true]);
        $this->client->repositories()->create(['owner' => 'acme', 'repo_name' => 'portal', 'default_branch' => 'main']);
    }

    private function claude(array $content, string $stopReason = 'tool_use'): array
    {
        return ['id' => 'msg', 'type' => 'message', 'role' => 'assistant', 'content' => $content, 'stop_reason' => $stopReason, 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]];
    }

    private function searchResponse(): array
    {
        return ['total_count' => 2, 'items' => [
            ['number' => 42, 'title' => 'Add SSO login', 'user' => ['login' => 'alice'], 'html_url' => 'https://github.com/acme/portal/pull/42', 'pull_request' => ['merged_at' => '2026-09-12T10:00:00Z']],
            ['number' => 40, 'title' => 'Fix invoice rounding', 'user' => ['login' => 'bob'], 'html_url' => 'https://github.com/acme/portal/pull/40', 'pull_request' => ['merged_at' => '2026-09-03T10:00:00Z']],
        ]];
    }

    private function formContext(): array
    {
        return [
            'mode' => 'create',
            'fields' => ['client_id' => (string) $this->client->id, 'client_name' => 'Campus Elite', 'issue_date' => '2026-10-04', 'due_date' => '2026-11-03', 'tax_rate' => '0'],
            'clients' => [['id' => $this->client->id, 'name' => 'Campus Elite']],
            'line_items' => [['line' => 1, 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]],
        ];
    }

    public function test_voice_bills_one_line_per_merged_pull_request_after_a_server_side_lookup(): void
    {
        Http::fake([
            'api.github.com/search/issues*' => Http::response($this->searchResponse()),
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->claude([
                    ['type' => 'tool_use', 'id' => 'toolu_find', 'name' => 'find_github_work', 'input' => ['kind' => 'merged_pull_requests', 'since' => '2026-09-01', 'until' => '2026-09-30']],
                ]))
                ->push($this->claude([
                    ['type' => 'text', 'text' => 'Added a line for each of the 2 pull requests merged in September.'],
                    ['type' => 'tool_use', 'id' => 'toolu_add', 'name' => 'add_github_lines', 'input' => ['refs' => ['all'], 'unit_price' => 150]],
                ])),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('voice.interpret'), [
            'transcript' => 'can you find all the can you add a line for each pull request merged in September',
            'page' => ['name' => 'invoices.form', 'context' => $this->formContext(), 'today' => '2026-10-04'],
        ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'Added a line for each of the 2 pull requests merged in September.')
            ->assertJsonPath('actions', [
                ['name' => 'add_line_item', 'input' => ['description' => 'PR #40: Fix invoice rounding', 'quantity' => 1, 'unit_price' => 150]],
                ['name' => 'add_line_item', 'input' => ['description' => 'PR #42: Add SSO login', 'quantity' => 1, 'unit_price' => 150]],
            ])
            ->assertJsonPath('usage.input', 200);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'api.github.com/search/issues')
            && str_contains(urldecode($r->url()), 'repo:acme/portal is:pr is:merged merged:2026-09-01..2026-09-30'));

        // The second Claude call carries the lookup result, and the screen told Claude about the linked repo.
        $claudeCalls = collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn (Request $r) => str_contains($r->url(), 'anthropic'))->values();
        $this->assertCount(2, $claudeCalls);
        $this->assertStringContainsString('acme/portal', $claudeCalls[0]['messages'][0]['content']);
        $toolResult = collect($claudeCalls[1]['messages'])->last()['content'][0];
        $this->assertSame('toolu_find', $toolResult['tool_use_id']);
        $this->assertStringContainsString('PR-1: #40 "Fix invoice rounding"', $toolResult['content']);
        $this->assertStringContainsString('PR-2: #42 "Add SSO login"', $toolResult['content']);
    }

    public function test_lookup_without_a_selected_client_tells_claude_instead_of_calling_github(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([], 500),
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->claude([
                    ['type' => 'tool_use', 'id' => 'toolu_find', 'name' => 'find_github_work', 'input' => ['kind' => 'merged_pull_requests', 'since' => '2026-09-01', 'until' => '2026-09-30']],
                ]))
                ->push($this->claude([['type' => 'text', 'text' => 'Which client is this invoice for?']], 'end_turn')),
        ]);

        $context = $this->formContext();
        $context['fields']['client_id'] = '';

        $this->actingAs($this->user)->postJson(route('voice.interpret'), [
            'transcript' => 'add a line for each PR merged in September',
            'page' => ['name' => 'invoices.form', 'context' => $context],
        ])->assertOk()->assertJsonPath('actions', [])->assertJsonPath('reply', 'Which client is this invoice for?');

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'api.github.com'));
    }

    public function test_lookup_rounds_are_capped(): void
    {
        $find = $this->claude([
            ['type' => 'tool_use', 'id' => 'toolu_find', 'name' => 'find_github_work', 'input' => ['kind' => 'merged_pull_requests', 'since' => '2026-09-01', 'until' => '2026-09-30']],
        ]);
        Http::fake([
            'api.github.com/*' => Http::response($this->searchResponse()),
            'api.anthropic.com/*' => Http::sequence()->push($find)->push($find)->push($find)->push($find),
        ]);

        $this->actingAs($this->user)->postJson(route('voice.interpret'), [
            'transcript' => 'bill september PRs',
            'page' => ['name' => 'invoices.form', 'context' => $this->formContext()],
        ])->assertOk()->assertJsonPath('actions', []);

        $this->assertCount(3, collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'anthropic')));
    }

    public function test_import_endpoint_lists_merged_pull_requests_and_commits_without_merge_commits(): void
    {
        Http::fake([
            'api.github.com/search/issues*' => Http::response($this->searchResponse()),
            'api.github.com/repos/acme/portal/commits*' => Http::response([
                ['sha' => 'abcdef1234567', 'commit' => ['message' => "Merge pull request #42 from acme/sso\n\nAdd SSO", 'author' => ['name' => 'Alice', 'email' => 'a@x', 'date' => '2026-09-12T10:00:00Z']]],
                ['sha' => '1234567abcdef', 'commit' => ['message' => "Speed up dashboard queries\n\nDetails", 'author' => ['name' => 'Bob', 'email' => 'b@x', 'date' => '2026-09-05T10:00:00Z']]],
            ]),
        ]);

        $this->actingAs($this->user)
            ->getJson(route('clients.github-activity', ['client' => $this->client, 'kind' => 'merged_pull_requests', 'since' => '2026-09-01', 'until' => '2026-09-30']))
            ->assertOk()
            ->assertJsonPath('repos', ['acme/portal'])
            ->assertJsonPath('items.0.description', 'PR #40: Fix invoice rounding')
            ->assertJsonPath('items.1.description', 'PR #42: Add SSO login');

        $this->actingAs($this->user)
            ->getJson(route('clients.github-activity', ['client' => $this->client, 'kind' => 'commits', 'since' => '2026-09-01', 'until' => '2026-09-30']))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.description', 'Speed up dashboard queries (commit 1234567)');
    }

    public function test_import_endpoint_explains_missing_repositories_and_token(): void
    {
        Http::fake();
        $other = Client::create(['company_name' => 'No Repos Ltd', 'billing_terms' => 'net_30', 'is_active' => true]);
        $query = ['kind' => 'merged_pull_requests', 'since' => '2026-09-01', 'until' => '2026-09-30'];

        $this->actingAs($this->user)
            ->getJson(route('clients.github-activity', ['client' => $other] + $query))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'No Repos Ltd has no GitHub repositories linked. Link them on the client page first.']);

        Config::set('services.github.token', null);
        $this->actingAs($this->user)
            ->getJson(route('clients.github-activity', ['client' => $this->client] + $query))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'GitHub is not connected. An admin needs to set GITHUB_TOKEN on the server.']);

        Http::assertNothingSent();
    }
}
