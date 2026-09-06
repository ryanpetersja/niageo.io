<x-app-layout voice-page="settings.ai-usage">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Settings'], ['label' => 'AI Usage & Budget']]" />
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight">AI Usage &amp; Budget</h2>
                <p class="text-sm text-muted mt-0.5">Every Claude call the app makes, priced at Anthropic list rates, with a spend limit you control.</p>
            </div>
            <a href="https://console.anthropic.com/settings/billing" target="_blank" rel="noopener" class="btn btn-secondary">Anthropic Console billing ↗</a>
        </div>
    </x-slot>

    @php
        $money = fn ($v, $d = 2) => '$' . number_format($v, $d);
        $tokens = fn ($n) => $n >= 1000000 ? number_format($n / 1000000, 2) . 'M' : ($n >= 1000 ? number_format($n / 1000, 1) . 'k' : (string) $n);
        $pct = $status['monthly']['percent'];
        $barColor = $pct === null ? 'var(--accent)' : ($pct >= 100 ? 'var(--danger)' : ($pct >= 80 ? 'var(--warn)' : 'var(--good)'));
        $maxDaily = max(0.0001, max(array_column($daily, 'cost')));
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ $errors->first() }}</div>
            @endif
            @if($status['exceeded'])
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">
                    The AI budget has been reached.
                    @if($status['blocked_all']) Every AI feature is paused until the budget resets on {{ $status['monthly']['resets_on']->format('M j') }} or the limit is raised.
                    @elseif($status['blocked_voice']) The voice assistant is paused until the budget resets on {{ $status['monthly']['resets_on']->format('M j') }} or the limit is raised.
                    @else Features keep working; only alerts are sent. @endif
                </div>
            @endif

            {{-- Headline numbers --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    ['Today', $today, null],
                    ['Last 7 days', $week, null],
                    ['Month to date', $month, $status['monthly']['limit'] ? 'of ' . $money($status['monthly']['limit']) . ' budget' : null],
                    ['Projected this month', ['cost' => $status['projected'], 'requests' => null], 'at the current daily pace'],
                ] as [$label, $data, $hint])
                    <div class="card p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-bold" style="color: {{ $label === 'Projected this month' && $status['monthly']['limit'] && $status['projected'] > $status['monthly']['limit'] ? 'var(--warn)' : 'var(--text)' }};">{{ $money($data['cost']) }}</div>
                        <div class="mt-1 text-xs text-faint">
                            @if($data['requests'] !== null)
                                {{ $data['requests'] }} request{{ $data['requests'] == 1 ? '' : 's' }} · {{ $tokens($data['tokens'] ?? 0) }} tokens
                            @endif
                            @if($hint)
                                {{ $data['requests'] !== null ? '·' : '' }} {{ $hint }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Budget + runway --}}
                <div class="card p-6 space-y-5">
                    <div>
                        <div class="flex justify-between items-baseline mb-2">
                            <h3 class="text-base font-semibold text-white">Monthly budget</h3>
                            @if($status['monthly']['limit'])
                                <span class="text-sm font-semibold" style="color: {{ $barColor }};">{{ $pct }}%</span>
                            @endif
                        </div>
                        @if($status['monthly']['limit'])
                            <div class="h-2.5 rounded-full overflow-hidden" style="background: var(--surface-3);">
                                <div class="h-full rounded-full" style="width: {{ min(100, $pct) }}%; background: {{ $barColor }};"></div>
                            </div>
                            <div class="mt-2 text-xs text-muted">
                                {{ $money($status['monthly']['spent']) }} spent of {{ $money($status['monthly']['limit']) }} ·
                                {{ $money($status['monthly']['remaining']) }} left · resets {{ $status['monthly']['resets_on']->format('M j') }}
                            </div>
                            <div class="mt-1 text-xs text-faint">When reached: {{ \App\Models\AiBudget::ACTIONS[$status['action']] ?? $status['action'] }}. Alerts at 50%, 80% and 100%.</div>
                        @else
                            <p class="text-sm text-muted">No budget set. Add one below to get alerts and, if you want, a hard stop.</p>
                        @endif
                    </div>

                    @if($status['daily']['limit'])
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <h4 class="text-sm font-semibold text-white">Daily budget</h4>
                                <span class="text-xs text-muted">{{ $money($status['daily']['spent']) }} of {{ $money($status['daily']['limit']) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full overflow-hidden" style="background: var(--surface-3);">
                                <div class="h-full rounded-full" style="width: {{ min(100, $status['daily']['percent']) }}%; background: {{ $status['daily']['exceeded'] ? 'var(--danger)' : 'var(--accent)' }};"></div>
                            </div>
                        </div>
                    @endif

                    <div class="pt-4 border-t" style="border-color: var(--border);">
                        <h4 class="text-sm font-semibold text-white mb-1">Prepaid credits</h4>
                        @if($runway)
                            <div class="text-2xl font-bold" style="color: {{ $runway['days_left'] !== null && $runway['days_left'] < 14 ? 'var(--warn)' : 'var(--text)' }};">{{ $money($runway['remaining']) }} <span class="text-sm font-normal text-muted">left</span></div>
                            <div class="text-xs text-muted mt-1">
                                {{ $money($runway['balance']) }} entered on {{ $runway['balance_at']->format('M j, Y') }}, {{ $money($runway['spent_since']) }} used since.
                                @if($runway['days_left'] !== null)
                                    At {{ $money($runway['daily_rate']) }}/day it lasts about <strong class="text-slate-200">{{ $runway['days_left'] }} days</strong> (until {{ $runway['runs_out_on']->format('M j, Y') }}).
                                @else
                                    No spend in the last 14 days, so no run-out date yet.
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-muted">Enter your Console credit balance below and the app will estimate how long it lasts.</p>
                        @endif
                    </div>
                </div>

                {{-- Daily chart --}}
                <div class="card p-6 lg:col-span-2">
                    <div class="flex justify-between items-baseline mb-4">
                        <h3 class="text-base font-semibold text-white">Daily spend, last 30 days</h3>
                        <span class="text-xs text-muted">peak {{ $money($maxDaily === 0.0001 ? 0 : $maxDaily) }}</span>
                    </div>
                    <div class="flex items-end gap-1 h-40">
                        @foreach($daily as $day)
                            <div class="flex-1 flex flex-col justify-end h-full group relative" title="{{ \Carbon\Carbon::parse($day['date'])->format('D, M j') }}: {{ $money($day['cost'], 4) }} · {{ $day['requests'] }} request{{ $day['requests'] == 1 ? '' : 's' }}">
                                <div class="rounded-t" style="height: {{ $day['cost'] > 0 ? max(3, round($day['cost'] / $maxDaily * 100)) : 1 }}%; background: {{ $day['cost'] > 0 ? 'var(--accent)' : 'var(--surface-3)' }};"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-xs text-faint mt-2">
                        <span>{{ \Carbon\Carbon::parse($daily[0]['date'])->format('M j') }}</span>
                        <span>{{ \Carbon\Carbon::parse($daily[14]['date'])->format('M j') }}</span>
                        <span>Today</span>
                    </div>
                    <p class="text-xs text-faint mt-3">Listening to the microphone costs nothing; only finalised commands and report or scope generations call Claude.</p>
                </div>
            </div>

            {{-- Breakdown --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-white mb-3">By feature <span class="text-xs font-normal text-faint">month to date</span></h3>
                    @if(empty($byFeature))<p class="text-sm text-muted">No usage yet this month.</p>@endif
                    <div class="divide-hair">
                        @foreach($byFeature as $row)
                            <div class="py-2 flex justify-between items-center text-sm">
                                <div><div class="text-slate-200">{{ $row['label'] }}</div><div class="text-xs text-faint">{{ $row['requests'] }} requests · {{ $tokens($row['tokens']) }} tokens</div></div>
                                <div class="font-medium text-slate-100">{{ $money($row['cost']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-white mb-3">By model <span class="text-xs font-normal text-faint">month to date</span></h3>
                    @if(empty($byModel))<p class="text-sm text-muted">No usage yet this month.</p>@endif
                    <div class="divide-hair">
                        @foreach($byModel as $row)
                            <div class="py-2 flex justify-between items-center text-sm">
                                <div><div class="text-slate-200">{{ $row['model'] }}@if(!$row['price_known']) <span class="badge badge-warn ml-1">price unknown</span>@endif</div><div class="text-xs text-faint">{{ $row['requests'] }} requests · {{ $tokens($row['tokens']) }} tokens</div></div>
                                <div class="font-medium text-slate-100">{{ $money($row['cost']) }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 pt-3 border-t text-xs text-faint" style="border-color: var(--border);">
                        Voice uses <span class="text-slate-300">{{ $voiceModel }}</span>; reports and scopes use <span class="text-slate-300">{{ $reportModel }}</span>.
                    </div>
                </div>
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-white mb-3">By user <span class="text-xs font-normal text-faint">month to date</span></h3>
                    @if(empty($byUser))<p class="text-sm text-muted">No usage yet this month.</p>@endif
                    <div class="divide-hair">
                        @foreach($byUser as $row)
                            <div class="py-2 flex justify-between items-center text-sm">
                                <div><div class="text-slate-200">{{ $row['name'] }}</div><div class="text-xs text-faint">{{ $row['requests'] }} requests</div></div>
                                <div class="font-medium text-slate-100">{{ $money($row['cost']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <div class="card p-6">
                <h3 class="text-base font-semibold text-white mb-1">Budget &amp; alerts</h3>
                <p class="text-sm text-muted mb-5">Limits apply to the app's estimate. Alerts go to the addresses below, or to every admin when empty.</p>
                <form method="POST" action="{{ route('settings.ai-usage.budget') }}" class="grid grid-cols-1 md:grid-cols-3 gap-5" data-voice-form="budget">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="monthly_budget" value="Monthly budget (USD)" />
                        <x-text-input id="monthly_budget" name="monthly_budget" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('monthly_budget', $budget->monthly_budget)" placeholder="e.g. 50" />
                        <p class="text-xs text-faint mt-1">Leave empty for no limit.</p>
                    </div>
                    <div>
                        <x-input-label for="daily_budget" value="Daily budget (USD, optional)" />
                        <x-text-input id="daily_budget" name="daily_budget" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('daily_budget', $budget->daily_budget)" placeholder="e.g. 5" />
                        <p class="text-xs text-faint mt-1">Catches a runaway day before the month is used up.</p>
                    </div>
                    <div>
                        <x-input-label for="action" value="When a budget is reached" />
                        <select id="action" name="action" class="field mt-1">
                            @foreach(\App\Models\AiBudget::ACTIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('action', $budget->action) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="credits_balance" value="Prepaid credit balance (USD)" />
                        <x-text-input id="credits_balance" name="credits_balance" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('credits_balance', $budget->credits_balance)" placeholder="From the Anthropic Console" />
                    </div>
                    <div>
                        <x-input-label for="credits_balance_at" value="Balance as of" />
                        <x-text-input id="credits_balance_at" name="credits_balance_at" type="date" class="mt-1 block w-full" :value="old('credits_balance_at', $budget->credits_balance_at?->format('Y-m-d'))" />
                        <p class="text-xs text-faint mt-1">Spend recorded from this date counts against the balance.</p>
                    </div>
                    <div>
                        <x-input-label for="alert_emails" value="Alert recipients" />
                        <x-text-input id="alert_emails" name="alert_emails" type="text" class="mt-1 block w-full" :value="old('alert_emails', $budget->alert_emails)" placeholder="Comma-separated; empty = all admins" />
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <x-primary-button>Save budget</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Recent requests --}}
            <div class="card p-6">
                <h3 class="text-base font-semibold text-white mb-3">Recent requests</h3>
                @if($recent->isEmpty())
                    <p class="text-sm text-muted">Nothing recorded yet. Usage appears here as soon as a voice command, report summary or scope generation runs.</p>
                @else
                    <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b" style="border-color: var(--border);">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">When</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">Feature</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">User</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">Model</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-muted uppercase">In</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-muted uppercase">Cached</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-muted uppercase">Out</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-muted uppercase">Cost</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-muted uppercase">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-hair">
                            @foreach($recent as $log)
                                <tr class="row-item text-sm">
                                    <td class="px-3 py-2 text-muted whitespace-nowrap">{{ $log->created_at->format('M j, H:i') }}</td>
                                    <td class="px-3 py-2 text-slate-200">{{ \App\Services\AiUsageService::featureLabel($log->feature) }}</td>
                                    <td class="px-3 py-2 text-muted">{{ $log->user?->name ?? 'System' }}</td>
                                    <td class="px-3 py-2 text-faint">{{ $log->model }}</td>
                                    <td class="px-3 py-2 text-right text-muted">{{ number_format($log->input_tokens + $log->cache_write_tokens) }}</td>
                                    <td class="px-3 py-2 text-right text-muted">{{ number_format($log->cache_read_tokens) }}</td>
                                    <td class="px-3 py-2 text-right text-muted">{{ number_format($log->output_tokens) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-100 font-medium">{{ $money($log->estimated_cost, 4) }}</td>
                                    <td class="px-3 py-2 text-right text-faint">{{ $log->duration_ms !== null ? number_format($log->duration_ms / 1000, 1) . 's' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>

            {{-- How it's priced --}}
            <div class="card p-6 text-sm text-muted space-y-2">
                <h3 class="text-base font-semibold text-white">How this is estimated</h3>
                <p>Costs use Anthropic's list prices per million tokens: input, output, cached input at {{ (int) round(config('ai_pricing.cache_read_multiplier', 0.1) * 100) }}% of the input price, and cache writes at {{ config('ai_pricing.cache_write_multiplier', 1.25) }}×. The Anthropic Console is the authoritative bill; this page exists so you can see the trend and get warned early.</p>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-faint">
                    @foreach($pricing as $model => $price)
                        <span><span class="text-slate-300">{{ $model }}</span> ${{ $price['input'] }} in / ${{ $price['output'] }} out</span>
                    @endforeach
                </div>
                <p>Set a monthly budget here and a spend limit in the Anthropic Console (Settings → Limits). If Console auto-reload is off, calls simply fail once credits run out, so there is no surprise charge, only downtime.</p>
            </div>
        </div>
    </div>
</x-app-layout>
