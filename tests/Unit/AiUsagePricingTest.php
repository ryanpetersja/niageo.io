<?php

namespace Tests\Unit;

use App\Services\AiUsageService;
use Tests\TestCase;

class AiUsagePricingTest extends TestCase
{
    public function test_costs_use_list_prices_with_cache_multipliers(): void
    {
        $service = app(AiUsageService::class);

        // 1M input at $5 + 1M cached at $0.50 + 1M cache-write at $6.25 + 1M output at $25
        [$cost, $known] = $service->cost('claude-opus-5', ['input' => 1_000_000, 'cache_read' => 1_000_000, 'cache_write' => 1_000_000, 'output' => 1_000_000]);
        $this->assertTrue($known);
        $this->assertEqualsWithDelta(36.75, $cost, 0.000001);

        // A typical voice command: 900 uncached, 2,900 cached, 120 out on Opus 5 ≈ 0.9 cents
        [$cost] = $service->cost('claude-opus-5', ['input' => 900, 'cache_read' => 2900, 'cache_write' => 0, 'output' => 120]);
        $this->assertEqualsWithDelta(0.00895, $cost, 0.000001);
    }

    public function test_dated_model_ids_match_by_prefix_and_unknown_models_are_flagged(): void
    {
        $service = app(AiUsageService::class);

        $this->assertSame(['input' => 3, 'output' => 15, 'known' => true], $service->priceFor('claude-sonnet-4-5-20250929'));
        $this->assertSame(['input' => 1, 'output' => 5, 'known' => true], $service->priceFor('claude-haiku-4-5'));

        $unknown = $service->priceFor('claude-future-9');
        $this->assertFalse($unknown['known']);
        [$cost, $known] = $service->cost('claude-future-9', ['input' => 1_000_000, 'cache_read' => 0, 'cache_write' => 0, 'output' => 0]);
        $this->assertFalse($known);
        $this->assertEqualsWithDelta(5.0, $cost, 0.000001);
    }
}
