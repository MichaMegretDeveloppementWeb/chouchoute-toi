@props(['title' => 'Connexion'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-page">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }} · {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">

    {{-- Dark mode anti-flash script, must run before the stylesheets. --}}
    @uiKitHead

    @vite(['resources/css/ui-kit.css', 'resources/js/ui-kit.js'])

    @livewireStyles
</head>
<body class="flex min-h-full items-center justify-center px-4 py-12 antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex flex-col items-center gap-y-4">
            {{-- Le logo est un lettrage sombre sur fond clair : il lui faut son
                 propre fond blanc pour rester lisible en mode sombre. --}}
            <img
                src="{{ asset('favicon/favicon.svg') }}"
                alt="{{ config('app.name') }}"
                class="h-28 w-28 rounded-full bg-white object-contain p-1 ring-1 ring-black/5 dark:ring-white/10"
            />
            <p class="text-[13px] text-secondary">Espace d'administration</p>
        </div>

        <x-ui.card class="shadow-sm ring-1 ring-black/5 dark:ring-white/5">
            {{ $slot }}
        </x-ui.card>
    </div>

    <x-ui.toast position="top-right" />

    @livewireScripts
</body>
</html>
