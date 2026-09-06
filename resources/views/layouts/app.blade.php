<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen app-shell">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b" style="border-color: var(--border);">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main data-voice-page="{{ $attributes->get('voice-page') }}">
                {{ $slot }}
            </main>
        </div>

        @php
            $voiceFlash = null;
            if (session('error')) {
                $voiceFlash = ['error', session('error')];
            } elseif (session('success')) {
                $voiceFlash = ['success', session('success')];
            } elseif (isset($errors) && $errors->any()) {
                $voiceFlash = ['error', 'The form could not be saved: ' . $errors->first()];
            }
        @endphp
        @if($voiceFlash)
            <div data-voice-flash="{{ $voiceFlash[0] }}" hidden>{{ $voiceFlash[1] }}</div>
        @endif
        <x-voice-assistant />
    </body>
</html>
