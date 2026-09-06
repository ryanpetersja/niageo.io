@props(['active'])

@php
$base = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none';
$classes = ($active ?? false)
            ? $base . ' border-indigo-400 text-white'
            : $base . ' border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
