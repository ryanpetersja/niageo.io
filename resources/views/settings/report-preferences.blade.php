<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Settings'], ['label' => 'Report Preferences']]" />
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Report AI Preferences</h2>
            <a href="{{ route('settings.branding') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to Settings</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Current Preference Rules -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Active Preference Rules</h3>
                    @if($preferences->last_distilled_at)
                        <span class="text-xs text-gray-500">Last updated: {{ $preferences->last_distilled_at->format('M d, Y \a\t g:i A') }}</span>
                    @endif
                </div>

                @if(!empty($preferences->rules) && count($preferences->rules) > 0)
                    <p class="text-sm text-gray-500 mb-4">These rules are automatically injected into every AI report generation prompt. They are derived from feedback submitted on individual reports.</p>
                    <ol class="list-decimal list-inside space-y-2">
                        @foreach($preferences->rules as $rule)
                            <li class="text-sm text-gray-700 pl-1">{{ $rule }}</li>
                        @endforeach
                    </ol>
                    <p class="text-xs text-gray-400 mt-4">{{ count($preferences->rules) }} of 15 max rules</p>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No preferences yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Submit feedback on a generated report to start building AI preferences.</p>
                    </div>
                @endif
            </div>

            <!-- Recent Feedback History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Feedback History</h3>

                @if($recentFeedback->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Report</th>
                                    <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Feedback</th>
                                    <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                                    <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentFeedback as $entry)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2">
                                            @if($entry->report)
                                                <a href="{{ route('reports.show', $entry->report) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $entry->report->report_number }}</a>
                                            @else
                                                <span class="text-gray-400">Deleted</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-gray-700 max-w-md">{{ Str::limit($entry->feedback, 120) }}</td>
                                        <td class="py-2 text-gray-500 whitespace-nowrap">{{ $entry->user->name ?? 'Unknown' }}</td>
                                        <td class="py-2">
                                            @if($entry->processed)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Processed</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-gray-500 whitespace-nowrap text-xs">{{ $entry->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No feedback has been submitted yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
