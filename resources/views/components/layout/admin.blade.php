{{--
    `wide` retire la largeur maximale et les marges du contenu.

    La colonne centrée convient à un formulaire ou à une liste, qu'on lit sur
    une ligne courte. Elle est fausse pour une grille temporelle : sur un grand
    écran elle laisse près d'un tiers de la largeur vide alors que chaque
    colonne de jour gagne à respirer.
--}}
@props(['title' => 'Administration', 'wide' => false])

@php
    $analytics = config('analytics.dashboard.route_name', 'analytics');
    $marketing = config('analytics.marketing.route_name', 'marketing');

    // Le prefixe vient de la configuration du package, comme pour analytics :
    // les ecrans peuvent etre montes ailleurs sans toucher a cette barre.
    $booking = config('booking.admin.route_name', 'booking.admin.');
    $admin = auth()->guard('admin')->user();
@endphp
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

    @vite([
        'resources/css/ui-kit.css',
        'resources/js/ui-kit.js',
        'packages/falcon-booking/resources/js/booking-admin.js',
    ])

    @livewireStyles
</head>
<body class="min-h-full antialiased">

    <x-ui.sidebar brand="Chouchoute-toi" :brand-logo="asset('favicon/favicon.svg')">
        <x-ui.sidebar.group label="Pilotage">
            <x-ui.sidebar.link
                :href="route('admin.dashboard')"
                icon="home"
                :active="request()->routeIs('admin.dashboard')">
                Tableau de bord
            </x-ui.sidebar.link>
        </x-ui.sidebar.group>

        <x-ui.sidebar.group label="Agenda">
            <x-ui.sidebar.link
                :href="route($booking.'agenda')"
                icon="calendar-days"
                :active="request()->routeIs($booking.'agenda')">
                Planning
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($booking.'catalogue')"
                icon="sparkles"
                :active="request()->routeIs($booking.'catalogue')">
                Prestations
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($booking.'categories')"
                icon="rectangle-stack"
                :active="request()->routeIs($booking.'categories')">
                Catégories
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($booking.'settings')"
                icon="adjustments-horizontal"
                :active="request()->routeIs($booking.'settings')">
                Réglages
            </x-ui.sidebar.link>
        </x-ui.sidebar.group>

        <x-ui.sidebar.group label="Audience">
            <x-ui.sidebar.link
                :href="route($analytics.'.overview')"
                icon="chart-pie"
                :active="request()->routeIs($analytics.'.overview')">
                Vue d'ensemble
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($analytics.'.realtime')"
                icon="signal"
                :active="request()->routeIs($analytics.'.realtime')">
                Temps réel
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($analytics.'.visitors')"
                icon="user-group"
                :active="request()->routeIs($analytics.'.visitors*')">
                Visiteurs
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($analytics.'.sessions')"
                icon="users"
                :active="request()->routeIs($analytics.'.sessions*')">
                Sessions
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($analytics.'.events')"
                icon="bolt"
                :active="request()->routeIs($analytics.'.events')">
                Événements
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($analytics.'.funnels')"
                icon="funnel"
                :active="request()->routeIs($analytics.'.funnels')">
                Tunnels
            </x-ui.sidebar.link>

            @if (trim((string) config('analytics.search_console.client_id')) !== '')
                <x-ui.sidebar.link
                    :href="route($analytics.'.integrations')"
                    icon="puzzle-piece"
                    :active="request()->routeIs($analytics.'.integrations*')">
                    Intégrations
                </x-ui.sidebar.link>
            @endif
        </x-ui.sidebar.group>

        <x-ui.sidebar.group label="Marketing">
            <x-ui.sidebar.link
                :href="route($marketing.'.dashboard')"
                icon="presentation-chart-line"
                :active="request()->routeIs($marketing.'.dashboard')">
                Vue d'ensemble
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($marketing.'.campaigns')"
                icon="megaphone"
                :active="request()->routeIs($marketing.'.campaigns*')">
                Campagnes
            </x-ui.sidebar.link>

            <x-ui.sidebar.link
                :href="route($marketing.'.ads')"
                icon="cursor-arrow-rays"
                :active="request()->routeIs($marketing.'.ads*')">
                Publicités
            </x-ui.sidebar.link>
        </x-ui.sidebar.group>

        <x-slot:user>
            <x-layout.admin-user-menu
                :name="$admin?->name ?? ''"
                :email="$admin?->email ?? ''"
            />
        </x-slot:user>
    </x-ui.sidebar>

    <div class="flex min-h-full flex-col lg:pl-[62px] wide:pl-[260px]">

        <header class="flex h-14 shrink-0 items-center justify-between border-b border-base bg-surface px-4 sm:px-6">
            <div class="flex items-center gap-x-3">
                <x-ui.sidebar.trigger />
                <span class="text-[13px] font-medium text-secondary">{{ $title }}</span>
            </div>

            {{-- « Voir le site » et « Déconnexion » vivent dans le menu utilisateur
                 de la sidebar : la topbar ne garde que le réglage d'affichage. --}}
            <div class="flex items-center gap-x-3">
                <x-ui.theme-toggle variant="switch" />
            </div>
        </header>

        <main class="flex-1">
            <div @class([
                'mx-auto max-w-[90em] px-4 py-6 sm:px-6 sm:py-8' => ! $wide,
                'h-full' => $wide,
            ])>
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-ui.toast position="top-right" />

    @livewireScripts
</body>
</html>
