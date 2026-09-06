<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Error Logs</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <!-- Toolbar -->
                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <form method="GET" action="{{ route('logs.index') }}" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Log file</label>
                            <select name="file" onchange="this.form.submit()" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @forelse($files as $f)
                                    <option value="{{ $f }}" @selected($f === $selected)>{{ $f }}</option>
                                @empty
                                    <option value="">No log files</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Level</label>
                            <select name="level" onchange="this.form.submit()" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All levels</option>
                                @foreach($levels as $lv)
                                    <option value="{{ $lv }}" @selected($lv === $level)>{{ $lv }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    <div class="ml-auto flex items-end gap-2">
                        @if($selected)
                            <a href="{{ route('logs.download', ['file' => $selected]) }}"
                               class="px-3 py-2 bg-gray-600 text-white rounded text-xs font-semibold uppercase hover:bg-gray-700">Download</a>
                            <form method="POST" action="{{ route('logs.clear') }}"
                                  onsubmit="return confirm('Clear all entries in {{ $selected }}? This cannot be undone.')">
                                @csrf
                                <input type="hidden" name="file" value="{{ $selected }}">
                                <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded text-xs font-semibold uppercase hover:bg-red-700">Clear</button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($selected)
                    <p class="text-xs text-gray-500 mb-4">
                        Showing <span class="font-medium">{{ count($entries) }}</span> {{ Str::plural('entry', count($entries)) }}
                        from <span class="font-mono">{{ $selected }}</span> ({{ number_format($size / 1024, 1) }} KB){{ $level ? ', filtered to '.$level : '' }}.
                        @if($truncated)
                            <span class="text-amber-600">Only the most recent portion of the file is shown (file is large).</span>
                        @endif
                    </p>
                @endif

                @php
                    $badge = [
                        'ERROR' => 'bg-red-100 text-red-800', 'CRITICAL' => 'bg-red-200 text-red-900',
                        'ALERT' => 'bg-red-200 text-red-900', 'EMERGENCY' => 'bg-red-200 text-red-900',
                        'WARNING' => 'bg-amber-100 text-amber-800', 'NOTICE' => 'bg-blue-100 text-blue-800',
                        'INFO' => 'bg-gray-100 text-gray-700', 'DEBUG' => 'bg-gray-100 text-gray-500',
                    ];
                @endphp

                <div class="space-y-2">
                    @forelse($entries as $e)
                        <details class="border border-gray-200 rounded-lg overflow-hidden group">
                            <summary class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 list-none">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold tracking-wide {{ $badge[$e['level']] ?? 'bg-gray-100 text-gray-700' }}">{{ $e['level'] }}</span>
                                <span class="font-mono text-xs text-gray-400 whitespace-nowrap mt-0.5">{{ $e['datetime'] }}</span>
                                <span class="text-sm text-gray-800 flex-1 break-words">{{ Str::limit($e['summary'], 180) }}</span>
                                @if($e['hasMore'])
                                    <span class="text-xs text-indigo-500 whitespace-nowrap mt-0.5 group-open:hidden">show trace</span>
                                @endif
                            </summary>
                            <pre class="bg-gray-900 text-gray-100 text-xs leading-relaxed p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ $e['body'] }}</pre>
                        </details>
                    @empty
                        <p class="text-center text-gray-400 py-12 text-sm">
                            @if(!$selected)
                                No log files found in <span class="font-mono">storage/logs</span>.
                            @else
                                No {{ $level ?: '' }} entries in this log.
                            @endif
                        </p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
