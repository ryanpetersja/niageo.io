<x-mail::message>
# AI usage alert

Claude usage in {{ config('app.name') }} has reached **{{ $threshold }}%** of this month's budget.

- Spent so far: **${{ number_format($spent, 2) }}** of ${{ number_format($budget, 2) }}
- Projected for the full month at the current pace: **${{ number_format($projected, 2) }}**
- Budget action: {{ $actionLabel }}

@if($threshold >= 100)
The budget is used up. {{ $action === 'warn' ? 'AI features keep working; only alerts are sent.' : 'The configured features are paused until the budget resets on the 1st or the limit is raised.' }}
@endif

<x-mail::button :url="$dashboardUrl">
Open the AI usage dashboard
</x-mail::button>

Estimated at Anthropic list prices; the Anthropic Console shows the actual bill.
</x-mail::message>
