<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings for AI spend limits, alerts and the credit balance.
 */
class AiBudget extends Model
{
    public const ACTIONS = [
        'warn' => 'Warn only (email alerts, banner in the app)',
        'block_voice' => 'Pause the voice assistant',
        'block_all' => 'Pause every AI feature',
    ];

    protected $fillable = [
        'monthly_budget',
        'daily_budget',
        'action',
        'credits_balance',
        'credits_balance_at',
        'alert_emails',
        'alerts_sent',
    ];

    protected $casts = [
        'monthly_budget' => 'float',
        'daily_budget' => 'float',
        'credits_balance' => 'float',
        'credits_balance_at' => 'date',
        'alerts_sent' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create(['action' => 'warn']);
    }

    /** Recipients for alerts: the configured list, or every admin user. */
    public function recipients(): array
    {
        $configured = array_values(array_filter(array_map('trim', explode(',', (string) $this->alert_emails)), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        if ($configured !== []) {
            return $configured;
        }

        return User::where('role', 'admin')->pluck('email')->all();
    }
}
