<?php

namespace App\Services;

use App\Mail\AiBudgetAlert;
use App\Models\AiBudget;
use App\Models\AiUsageLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Records every Claude call, prices it, and enforces the spend budget.
 */
class AiUsageService
{
    public const FEATURES = [
        'voice' => 'Voice assistant',
        'report_summary' => 'Report summaries',
        'report_revision' => 'Report revisions',
        'server_summary' => 'Server activity summaries',
        'feedback_rules' => 'Report preference rules',
        'scope_sections' => 'Scope builder — sections',
        'scope_items' => 'Scope builder — items',
        'scope_refine' => 'Scope builder — refine',
    ];

    public const THRESHOLDS = [50, 80, 100];

    /* ------------------------------------------------------------ recording */

    /**
     * Store the usage of one API response. Never throws: a failure to log must not break the feature.
     */
    public function record(string $feature, ?string $model, array $body, ?int $durationMs = null): ?AiUsageLog
    {
        try {
            $usage = is_array($body['usage'] ?? null) ? $body['usage'] : null;
            if (! $usage) {
                return null;
            }

            $model = $model ?: (string) ($body['model'] ?? 'unknown');
            $tokens = [
                'input' => (int) ($usage['input_tokens'] ?? 0),
                'cache_read' => (int) ($usage['cache_read_input_tokens'] ?? 0),
                'cache_write' => (int) ($usage['cache_creation_input_tokens'] ?? 0),
                'output' => (int) ($usage['output_tokens'] ?? 0),
            ];
            [$cost, $known] = $this->cost($model, $tokens);

            $log = AiUsageLog::create([
                'user_id' => auth()->id(),
                'feature' => $feature,
                'model' => $model,
                'input_tokens' => $tokens['input'],
                'cache_read_tokens' => $tokens['cache_read'],
                'cache_write_tokens' => $tokens['cache_write'],
                'output_tokens' => $tokens['output'],
                'estimated_cost' => $cost,
                'price_known' => $known,
                'duration_ms' => $durationMs,
            ]);

            $this->checkAlerts();

            return $log;
        } catch (\Throwable $e) {
            Log::warning('AI usage could not be recorded', ['feature' => $feature, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array{input: int, cache_read: int, cache_write: int, output: int}  $tokens
     * @return array{0: float, 1: bool}  [estimated USD, whether the model price is known]
     */
    public function cost(string $model, array $tokens): array
    {
        $price = $this->priceFor($model);
        $readMultiplier = (float) config('ai_pricing.cache_read_multiplier', 0.1);
        $writeMultiplier = (float) config('ai_pricing.cache_write_multiplier', 1.25);

        $cost = ($tokens['input'] ?? 0) * $price['input']
            + ($tokens['cache_read'] ?? 0) * $price['input'] * $readMultiplier
            + ($tokens['cache_write'] ?? 0) * $price['input'] * $writeMultiplier
            + ($tokens['output'] ?? 0) * $price['output'];

        return [round($cost / 1_000_000, 6), $price['known']];
    }

    /** Exact match, then the longest matching prefix (dated ids), then the default. */
    public function priceFor(string $model): array
    {
        $models = config('ai_pricing.models', []);
        if (isset($models[$model])) {
            return $models[$model] + ['known' => true];
        }

        $best = null;
        foreach (array_keys($models) as $key) {
            if (str_starts_with($model, $key) && ($best === null || strlen($key) > strlen($best))) {
                $best = $key;
            }
        }
        if ($best !== null) {
            return $models[$best] + ['known' => true];
        }

        return config('ai_pricing.default', ['input' => 5, 'output' => 25]) + ['known' => false];
    }

    public static function featureLabel(string $feature): string
    {
        return self::FEATURES[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
    }

    /* ------------------------------------------------------------ summaries */

    /**
     * @return array{requests: int, cost: float, input: int, cached: int, output: int, tokens: int}
     */
    public function totals(Carbon $from, ?Carbon $to = null): array
    {
        $row = AiUsageLog::query()
            ->where('created_at', '>=', $from)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->selectRaw('COUNT(*) as requests, COALESCE(SUM(estimated_cost),0) as cost, COALESCE(SUM(input_tokens + cache_write_tokens),0) as input, COALESCE(SUM(cache_read_tokens),0) as cached, COALESCE(SUM(output_tokens),0) as output')
            ->first();

        return [
            'requests' => (int) $row->requests,
            'cost' => (float) $row->cost,
            'input' => (int) $row->input,
            'cached' => (int) $row->cached,
            'output' => (int) $row->output,
            'tokens' => (int) $row->input + (int) $row->cached + (int) $row->output,
        ];
    }

    public function today(): array
    {
        return $this->totals(now()->startOfDay());
    }

    public function lastDays(int $days): array
    {
        return $this->totals(now()->subDays($days - 1)->startOfDay());
    }

    public function monthToDate(): array
    {
        return $this->totals(now()->startOfMonth());
    }

    /** Month-to-date spend scaled to the full month. */
    public function projectedMonth(): float
    {
        $spent = $this->monthToDate()['cost'];
        $elapsed = max(1, now()->day);

        return round($spent / $elapsed * now()->daysInMonth, 2);
    }

    public function averageDailyCost(int $days = 14): float
    {
        return round($this->lastDays($days)['cost'] / max(1, $days), 4);
    }

    /** Cost and request count per day for the last N days, gaps filled with zeros. */
    public function daily(int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = AiUsageLog::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(estimated_cost) as cost, COUNT(*) as requests')
            ->groupBy('day')
            ->pluck('cost', 'day');
        $counts = AiUsageLog::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as requests')
            ->groupBy('day')
            ->pluck('requests', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $series[] = ['date' => $day, 'cost' => (float) ($rows[$day] ?? 0), 'requests' => (int) ($counts[$day] ?? 0)];
        }

        return $series;
    }

    public function byFeature(Carbon $from): array
    {
        return AiUsageLog::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('feature, COUNT(*) as requests, SUM(estimated_cost) as cost, SUM(input_tokens + cache_read_tokens + cache_write_tokens + output_tokens) as tokens')
            ->groupBy('feature')
            ->orderByDesc('cost')
            ->get()
            ->map(fn ($r) => ['feature' => $r->feature, 'label' => self::featureLabel($r->feature), 'requests' => (int) $r->requests, 'cost' => (float) $r->cost, 'tokens' => (int) $r->tokens])
            ->all();
    }

    public function byModel(Carbon $from): array
    {
        return AiUsageLog::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('model, COUNT(*) as requests, SUM(estimated_cost) as cost, SUM(input_tokens + cache_read_tokens + cache_write_tokens + output_tokens) as tokens, MIN(price_known) as price_known')
            ->groupBy('model')
            ->orderByDesc('cost')
            ->get()
            ->map(fn ($r) => ['model' => $r->model, 'requests' => (int) $r->requests, 'cost' => (float) $r->cost, 'tokens' => (int) $r->tokens, 'price_known' => (bool) $r->price_known])
            ->all();
    }

    public function byUser(Carbon $from): array
    {
        return AiUsageLog::query()
            ->leftJoin('users', 'users.id', '=', 'ai_usage_logs.user_id')
            ->where('ai_usage_logs.created_at', '>=', $from)
            ->selectRaw("COALESCE(users.name, 'System (scheduled)') as name, COUNT(*) as requests, SUM(estimated_cost) as cost")
            ->groupBy('name')
            ->orderByDesc('cost')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'requests' => (int) $r->requests, 'cost' => (float) $r->cost])
            ->all();
    }

    public function recent(int $limit = 30)
    {
        return AiUsageLog::with('user')->latest()->limit($limit)->get();
    }

    /* --------------------------------------------------------------- budget */

    public function budgetStatus(?AiBudget $budget = null): array
    {
        $budget ??= AiBudget::current();
        $month = $this->monthToDate()['cost'];
        $day = $this->today()['cost'];

        $monthly = $this->limitStatus($budget->monthly_budget, $month);
        $daily = $this->limitStatus($budget->daily_budget, $day);
        $exceeded = $monthly['exceeded'] || $daily['exceeded'];

        return [
            'monthly' => $monthly + ['resets_on' => now()->addMonthNoOverflow()->startOfMonth()],
            'daily' => $daily,
            'action' => $budget->action,
            'exceeded' => $exceeded,
            'blocked_voice' => $exceeded && in_array($budget->action, ['block_voice', 'block_all'], true),
            'blocked_all' => $exceeded && $budget->action === 'block_all',
            'projected' => $this->projectedMonth(),
        ];
    }

    protected function limitStatus(?float $limit, float $spent): array
    {
        if (! $limit || $limit <= 0) {
            return ['limit' => null, 'spent' => $spent, 'percent' => null, 'remaining' => null, 'exceeded' => false];
        }

        return [
            'limit' => $limit,
            'spent' => $spent,
            'percent' => (int) round(min(999, $spent / $limit * 100)),
            'remaining' => max(0, $limit - $spent),
            'exceeded' => $spent >= $limit,
        ];
    }

    /** Whether the budget action currently stops this feature from calling Claude. */
    public function isBlocked(string $feature): bool
    {
        try {
            $status = $this->budgetStatus();
        } catch (\Throwable $e) {
            return false;
        }

        return $status['blocked_all'] || ($feature === 'voice' && $status['blocked_voice']);
    }

    /** How long the entered credit balance lasts at the recent daily rate. */
    public function runway(?AiBudget $budget = null): ?array
    {
        $budget ??= AiBudget::current();
        if (! $budget->credits_balance || ! $budget->credits_balance_at) {
            return null;
        }

        $spentSince = $this->totals(Carbon::instance($budget->credits_balance_at)->startOfDay())['cost'];
        $remaining = max(0, $budget->credits_balance - $spentSince);
        $rate = $this->averageDailyCost(14);
        $days = $rate > 0 ? (int) floor($remaining / $rate) : null;

        return [
            'balance' => (float) $budget->credits_balance,
            'balance_at' => $budget->credits_balance_at,
            'spent_since' => $spentSince,
            'remaining' => $remaining,
            'daily_rate' => $rate,
            'days_left' => $days,
            'runs_out_on' => $days !== null ? now()->addDays($days) : null,
        ];
    }

    /** Email once per threshold per month when month-to-date spend crosses 50/80/100% of the budget. */
    public function checkAlerts(): void
    {
        $budget = AiBudget::current();
        if (! $budget->monthly_budget || $budget->monthly_budget <= 0) {
            return;
        }

        $spent = $this->monthToDate()['cost'];
        $percent = $spent / $budget->monthly_budget * 100;
        $key = now()->format('Y-m');
        $sent = $budget->alerts_sent ?? [];
        $already = $sent[$key] ?? [];

        foreach (self::THRESHOLDS as $threshold) {
            if ($percent >= $threshold && ! in_array($threshold, $already, true)) {
                $already[] = $threshold;
                $sent[$key] = $already;
                $budget->update(['alerts_sent' => $sent]);

                $recipients = $budget->recipients();
                if ($recipients !== []) {
                    Mail::to($recipients)->send(new AiBudgetAlert($threshold, $spent, (float) $budget->monthly_budget, $this->projectedMonth(), $budget->action));
                }
            }
        }
    }
}
