{{--
    `wide` drops the content's maximum width and margins.

    The centred column suits a form or a list, read on a short line. It is wrong
    for a time grid, where it leaves nearly a third of a large screen empty
    while every day column gains from the room.
--}}
@props(['title' => 'Administration', 'wide' => false])

@php
    $analytics = config('analytics.dashboard.route_name', 'analytics');
    $marketing = config('analytics.marketing.route_name', 'marketing');

    // The prefix comes from the package's configuration, as for analytics: the
    // screens can be mounted elsewhere without touching this sidebar.
    $booking = config('booking.admin.route_name', 'booking.admin.');
    $admin = auth()->guard('admin')->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-page {{ falcon_theme_class() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }} · {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">

    {{-- The sidebar's state, written before paint: read afterwards, the sidebar
         would change width under the eye.

         With nothing remembered: folded below 1500px, open beyond. That default
         is not written to storage, so another screen finds its own. --}}
    <script>
        try {
            var stored = localStorage.getItem('fb-sidebar');

            document.documentElement.dataset.fbSidebar = (stored === 'collapsed' || stored === 'expanded')
                ? stored
                : (window.innerWidth >= 1500 ? 'expanded' : 'collapsed');
        } catch (e) {
            document.documentElement.dataset.fbSidebar = 'collapsed';
        }
    </script>

    {{-- One sheet and one script for this space, carrying everything. The kit
         and the packages are imported a line each inside them. --}}
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])

    @livewireStyles
</head>
<body class="min-h-full antialiased">

    {{--
        A section's active state is computed from its route prefix and not from
        the list of its links: « Intégrations » only appears when the Search
        Console is configured, so the section cannot be deduced from what it
        holds.
    --}}
    <x-app-ui::sidebar brand="Chouchoute-toi" :brand-logo="asset('favicon/favicon.svg')">
        <x-app-ui::sidebar.group>
            <x-app-ui::sidebar.link
                :href="route('admin.dashboard')"
                icon="home"
                :active="request()->routeIs('admin.dashboard')">
                Tableau de bord
            </x-app-ui::sidebar.link>

            {{-- `href`: the section's first page. The label leads there while we
                 are not in it, and becomes a plain toggle once inside. --}}
            <x-app-ui::sidebar.collapsible
                name="agenda"
                label="Agenda"
                icon="calendar-days"
                :href="route($booking.'agenda')"
                :active="request()->routeIs($booking.'*')">
                <x-app-ui::sidebar.link
                    :href="route($booking.'agenda')"
                    :active="request()->routeIs($booking.'agenda')">
                    Planning
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($booking.'catalogue')"
                    :active="request()->routeIs($booking.'catalogue')">
                    Prestations
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($booking.'categories')"
                    :active="request()->routeIs($booking.'categories')">
                    Catégories
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($booking.'journal')"
                    :active="request()->routeIs($booking.'journal')">
                    Journal
                </x-app-ui::sidebar.link>

                {{-- A third level: the settings are a section of their own, one
                     page per family, opening hours among them. --}}
                <x-app-ui::sidebar.collapsible
                    name="settings"
                    label="Réglages"
                    :href="route($booking.'settings')"
                    :active="request()->routeIs($booking.'settings*') || request()->routeIs($booking.'schedule')">
                    <x-app-ui::sidebar.link
                        :href="route($booking.'settings')"
                        :active="request()->routeIs($booking.'settings')">
                        Établissement
                    </x-app-ui::sidebar.link>

                    <x-app-ui::sidebar.link
                        :href="route($booking.'schedule')"
                        :active="request()->routeIs($booking.'schedule')">
                        Horaires
                    </x-app-ui::sidebar.link>

                    <x-app-ui::sidebar.link
                        :href="route($booking.'settings.slots')"
                        :active="request()->routeIs($booking.'settings.slots')">
                        Créneaux
                    </x-app-ui::sidebar.link>

                    <x-app-ui::sidebar.link
                        :href="route($booking.'settings.booking-window')"
                        :active="request()->routeIs($booking.'settings.booking-window')">
                        Réservation
                    </x-app-ui::sidebar.link>

                    <x-app-ui::sidebar.link
                        :href="route($booking.'settings.clients')"
                        :active="request()->routeIs($booking.'settings.clients')">
                        Clients
                    </x-app-ui::sidebar.link>

                    <x-app-ui::sidebar.link
                        :href="route($booking.'settings.notifications')"
                        :active="request()->routeIs($booking.'settings.notifications')">
                        Notifications
                    </x-app-ui::sidebar.link>
                </x-app-ui::sidebar.collapsible>
            </x-app-ui::sidebar.collapsible>

            <x-app-ui::sidebar.collapsible
                name="audience"
                label="Audience"
                icon="chart-pie"
                :href="route($analytics.'.overview')"
                :active="request()->routeIs($analytics.'.*')">
                <x-app-ui::sidebar.link
                    :href="route($analytics.'.overview')"
                    :active="request()->routeIs($analytics.'.overview')">
                    Vue d'ensemble
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($analytics.'.realtime')"
                    :active="request()->routeIs($analytics.'.realtime')">
                    Temps réel
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($analytics.'.visitors')"
                    :active="request()->routeIs($analytics.'.visitors*')">
                    Visiteurs
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($analytics.'.sessions')"
                    :active="request()->routeIs($analytics.'.sessions*')">
                    Sessions
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($analytics.'.events')"
                    :active="request()->routeIs($analytics.'.events')">
                    Événements
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($analytics.'.funnels')"
                    :active="request()->routeIs($analytics.'.funnels')">
                    Tunnels
                </x-app-ui::sidebar.link>

                @if (trim((string) config('analytics.search_console.client_id')) !== '')
                    <x-app-ui::sidebar.link
                        :href="route($analytics.'.integrations')"
                        :active="request()->routeIs($analytics.'.integrations*')">
                        Intégrations
                    </x-app-ui::sidebar.link>
                @endif
            </x-app-ui::sidebar.collapsible>

            <x-app-ui::sidebar.collapsible
                name="marketing"
                label="Marketing"
                icon="megaphone"
                :href="route($marketing.'.dashboard')"
                :active="request()->routeIs($marketing.'.*')">
                <x-app-ui::sidebar.link
                    :href="route($marketing.'.dashboard')"
                    :active="request()->routeIs($marketing.'.dashboard')">
                    Vue d'ensemble
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($marketing.'.campaigns')"
                    :active="request()->routeIs($marketing.'.campaigns*')">
                    Campagnes
                </x-app-ui::sidebar.link>

                <x-app-ui::sidebar.link
                    :href="route($marketing.'.ads')"
                    :active="request()->routeIs($marketing.'.ads*')">
                    Publicités
                </x-app-ui::sidebar.link>
            </x-app-ui::sidebar.collapsible>
        </x-app-ui::sidebar.group>

        <x-slot:user>
            <x-layout.admin-user-menu
                :name="$admin?->name ?? ''"
                :email="$admin?->email ?? ''"
            />
        </x-slot:user>
    </x-app-ui::sidebar>

    <div class="flex min-h-full flex-col lg:pl-[62px] wide:pl-[260px]">

        <header class="flex h-14 shrink-0 items-center justify-between border-b border-base bg-surface px-4 sm:px-6">
            <div class="flex items-center gap-x-3">
                {{-- A stated height rather than a wider padding: the kit sizes
                     this button by its `p-1.5`, and two values of the same
                     utility do not cancel each other. --}}
                <x-app-ui::sidebar.trigger class="max-sm:flex max-sm:h-11 max-sm:w-11 max-sm:items-center max-sm:justify-center" />

                {{-- The counterpart of the mobile trigger, and the same glyph:
                     the same gesture at the other end of the scale. An arrow
                     would state a direction, and this button toggles.

                     From `lg` and not from `wide`: hover no longer unfolds the
                     sidebar, so without this button the labels would be out of
                     reach between 1024 and 1500px. --}}
                <button type="button" onclick="toggleSidebar()"
                    class="hidden rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 lg:inline-flex dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    aria-label="Replier ou deployer la barre laterale">
                    <x-ui.icon name="bars-3" class="h-5 w-5" />
                </button>

                <span class="text-[13px] font-medium text-secondary">{{ $title }}</span>
            </div>

            {{-- The default variant rather than the switch: the switch takes
                 84px for three elements of which only one is a target, 36 wide
                 and 20 high. A single button says the same and can be aimed
                 at. --}}
            <div class="flex items-center gap-x-3">
                <x-ui.theme-toggle class="max-sm:flex max-sm:h-11 max-sm:w-11 max-sm:items-center max-sm:justify-center" />
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

    <x-app-ui::toast position="top-right" />

    {{-- No directive from the kit nor from the packages: their scripts are
         imported in resources/js/admin.js and compiled with ours. --}}
    @livewireScripts
</body>
</html>
