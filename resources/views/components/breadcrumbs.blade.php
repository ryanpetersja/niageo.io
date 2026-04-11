@props(['items' => []])

<nav class="flex items-center text-sm text-gray-500 mb-1" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
    </a>
    @foreach($items as $item)
        <svg class="w-4 h-4 mx-1 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if($loop->last)
            <span class="text-gray-600 font-medium truncate">{{ $item['label'] }}</span>
        @else
            <a href="{{ $item['url'] }}" class="hover:text-gray-700 transition truncate">{{ $item['label'] }}</a>
        @endif
    @endforeach
</nav>
