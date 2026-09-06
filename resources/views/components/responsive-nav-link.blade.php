@props(['active'])

@php
$base = 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out focus:outline-none';
$classes = ($active ?? false)
            ? $base . ' border-indigo-400 text-indigo-200 bg-indigo-500/10'
            : $base . ' border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-700/40 hover:border-slate-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
