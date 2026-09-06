<?php

namespace App\Http\Controllers;

use App\Models\AiBudget;
use App\Services\AiUsageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiUsageController extends Controller
{
    public function __construct(private AiUsageService $usage) {}

    public function index()
    {
        $budget = AiBudget::current();
        $monthStart = now()->startOfMonth();

        return view('settings.ai-usage', [
            'budget' => $budget,
            'today' => $this->usage->today(),
            'week' => $this->usage->lastDays(7),
            'month' => $this->usage->monthToDate(),
            'allTime' => $this->usage->totals(now()->subYears(10)),
            'status' => $this->usage->budgetStatus($budget),
            'runway' => $this->usage->runway($budget),
            'daily' => $this->usage->daily(30),
            'byFeature' => $this->usage->byFeature($monthStart),
            'byModel' => $this->usage->byModel($monthStart),
            'byUser' => $this->usage->byUser($monthStart),
            'recent' => $this->usage->recent(30),
            'pricing' => config('ai_pricing.models', []),
            'voiceModel' => config('services.anthropic.voice_model'),
            'reportModel' => config('services.anthropic.model'),
        ]);
    }

    public function updateBudget(Request $request)
    {
        $validated = $request->validate([
            'monthly_budget' => 'nullable|numeric|min:0|max:100000',
            'daily_budget' => 'nullable|numeric|min:0|max:10000',
            'action' => ['required', Rule::in(array_keys(AiBudget::ACTIONS))],
            'credits_balance' => 'nullable|numeric|min:0|max:1000000',
            'credits_balance_at' => 'nullable|date|required_with:credits_balance',
            'alert_emails' => 'nullable|string|max:500',
        ]);

        $budget = AiBudget::current();
        $monthlyChanged = (float) ($validated['monthly_budget'] ?? 0) !== (float) ($budget->monthly_budget ?? 0);

        $budget->update([
            'monthly_budget' => $validated['monthly_budget'] !== null && $validated['monthly_budget'] !== '' ? $validated['monthly_budget'] : null,
            'daily_budget' => $validated['daily_budget'] !== null && $validated['daily_budget'] !== '' ? $validated['daily_budget'] : null,
            'action' => $validated['action'],
            'credits_balance' => $validated['credits_balance'] !== null && $validated['credits_balance'] !== '' ? $validated['credits_balance'] : null,
            'credits_balance_at' => $validated['credits_balance_at'] ?? null,
            'alert_emails' => $validated['alert_emails'] ?? null,
            // A new limit starts a fresh set of alerts for this month.
            'alerts_sent' => $monthlyChanged ? [] : $budget->alerts_sent,
        ]);

        return redirect()->route('settings.ai-usage')->with('success', 'AI budget settings saved.');
    }
}
