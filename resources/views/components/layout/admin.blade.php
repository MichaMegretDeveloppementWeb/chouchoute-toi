{{--
    Le back-office · la coquille du kit, et notre navigation dedans.

    Le kit écrit le document, les deux piles d'assets, la classe du thème, la
    barre et le conteneur des notifications. Ce fichier n'apporte que ce qui est
    à nous : la marque, l'arborescence des écrans et le menu du compte.

    `wide` retire la largeur maximale du contenu. La colonne centrée convient à
    un formulaire ou à une liste, lus sur une ligne courte ; elle est mauvaise
    pour une grille horaire, où elle laisse près d'un tiers d'un grand écran
    vide alors que chaque colonne de jour y gagnerait.
--}}
@props(['title' => 'Administration', 'wide' => false])

@php
    // Les noms de routes des paquets sont fixes et s'écrivent en clair · seules
    // leurs adresses se règlent. Booking expose encore son préfixe en
    // configuration, analytics ne le fait plus.
    $analytics = 'analytics.admin';
    $marketing = 'analytics.admin.marketing';
    $booking = config('booking.admin.route_name', 'booking.admin.');
    $admin = auth()->guard('admin')->user();
@endphp

<x-ui::layouts.admin :title="$title" :wide="$wide">

    <x-slot:head>
        {{-- Une surface d'administration privée : tenue hors des moteurs. --}}
        <meta name="robots" content="noindex, nofollow">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">

        {{-- Notre feuille et notre script, après ceux du kit et des paquets :
             les nôtres arrivent en dernier, donc ils gagnent. --}}
        @vite(['resources/css/admin.css', 'resources/js/admin.js'])

        @livewireStyles
    </x-slot:head>

    <x-slot:sidebar>
        {{--
            L'état actif d'une section se calcule sur son préfixe de route et non
            sur la liste de ses liens : « Intégrations » ne paraît que si la
            Search Console est configurée, donc la section ne peut pas se
            déduire de ce qu'elle contient.
        --}}
        <x-ui::sidebar brand="Chouchoute-toi" :brand-logo="asset('favicon/favicon.svg')">
            <x-ui::sidebar.group>
                <x-ui::sidebar.link
                    :href="route('admin.dashboard')"
                    icon="home"
                    :active="request()->routeIs('admin.dashboard')">
                    Tableau de bord
                </x-ui::sidebar.link>

                {{-- `href` : la première page de la section. Le libellé y mène
                     tant qu'on n'y est pas, et devient un simple replieur une
                     fois dedans. --}}
                <x-ui::sidebar.collapsible
                    name="agenda"
                    label="Agenda"
                    icon="calendar-days"
                    :href="route($booking.'agenda')"
                    :active="request()->routeIs($booking.'*')">
                    <x-ui::sidebar.link
                        :href="route($booking.'agenda')"
                        :active="request()->routeIs($booking.'agenda')">
                        Planning
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($booking.'catalogue')"
                        :active="request()->routeIs($booking.'catalogue')">
                        Prestations
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($booking.'categories')"
                        :active="request()->routeIs($booking.'categories')">
                        Catégories
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($booking.'journal')"
                        :active="request()->routeIs($booking.'journal')">
                        Journal
                    </x-ui::sidebar.link>

                    {{-- Un troisième niveau : les réglages sont une section à
                         eux, une page par famille, les horaires compris. --}}
                    <x-ui::sidebar.collapsible
                        name="settings"
                        label="Réglages"
                        :href="route($booking.'settings')"
                        :active="request()->routeIs($booking.'settings*') || request()->routeIs($booking.'schedule')">
                        <x-ui::sidebar.link
                            :href="route($booking.'settings')"
                            :active="request()->routeIs($booking.'settings')">
                            Établissement
                        </x-ui::sidebar.link>

                        <x-ui::sidebar.link
                            :href="route($booking.'schedule')"
                            :active="request()->routeIs($booking.'schedule')">
                            Horaires
                        </x-ui::sidebar.link>

                        <x-ui::sidebar.link
                            :href="route($booking.'settings.slots')"
                            :active="request()->routeIs($booking.'settings.slots')">
                            Créneaux
                        </x-ui::sidebar.link>

                        <x-ui::sidebar.link
                            :href="route($booking.'settings.booking-window')"
                            :active="request()->routeIs($booking.'settings.booking-window')">
                            Réservation
                        </x-ui::sidebar.link>

                        <x-ui::sidebar.link
                            :href="route($booking.'settings.clients')"
                            :active="request()->routeIs($booking.'settings.clients')">
                            Clients
                        </x-ui::sidebar.link>

                        <x-ui::sidebar.link
                            :href="route($booking.'settings.notifications')"
                            :active="request()->routeIs($booking.'settings.notifications')">
                            Notifications
                        </x-ui::sidebar.link>
                    </x-ui::sidebar.collapsible>
                </x-ui::sidebar.collapsible>

                <x-ui::sidebar.collapsible
                    name="audience"
                    label="Audience"
                    icon="chart-pie"
                    :href="route($analytics.'.overview')"
                    :active="request()->routeIs($analytics.'.*') && ! request()->routeIs($marketing.'.*')">
                    <x-ui::sidebar.link
                        :href="route($analytics.'.overview')"
                        :active="request()->routeIs($analytics.'.overview')">
                        Vue d'ensemble
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($analytics.'.realtime')"
                        :active="request()->routeIs($analytics.'.realtime')">
                        Temps réel
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($analytics.'.visitors')"
                        :active="request()->routeIs($analytics.'.visitors*')">
                        Visiteurs
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($analytics.'.sessions')"
                        :active="request()->routeIs($analytics.'.sessions*')">
                        Sessions
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($analytics.'.events')"
                        :active="request()->routeIs($analytics.'.events')">
                        Événements
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($analytics.'.funnels')"
                        :active="request()->routeIs($analytics.'.funnels')">
                        Tunnels
                    </x-ui::sidebar.link>

                    @if (trim((string) config('analytics.search_console.client_id')) !== '')
                        <x-ui::sidebar.link
                            :href="route($analytics.'.integrations')"
                            :active="request()->routeIs($analytics.'.integrations*')">
                            Intégrations
                        </x-ui::sidebar.link>
                    @endif
                </x-ui::sidebar.collapsible>

                <x-ui::sidebar.collapsible
                    name="marketing"
                    label="Marketing"
                    icon="megaphone"
                    :href="route($marketing.'.dashboard')"
                    :active="request()->routeIs($marketing.'.*')">
                    <x-ui::sidebar.link
                        :href="route($marketing.'.dashboard')"
                        :active="request()->routeIs($marketing.'.dashboard')">
                        Vue d'ensemble
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($marketing.'.campaigns')"
                        :active="request()->routeIs($marketing.'.campaigns*')">
                        Campagnes
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($marketing.'.ads')"
                        :active="request()->routeIs($marketing.'.ads*')">
                        Publicités
                    </x-ui::sidebar.link>
                </x-ui::sidebar.collapsible>
            </x-ui::sidebar.group>

            <x-slot:user>
                <x-layout.admin-user-menu
                    :name="$admin?->name ?? ''"
                    :email="$admin?->email ?? ''"
                />
            </x-slot:user>
        </x-ui::sidebar>
    </x-slot:sidebar>

    {{ $slot }}

    <x-slot:scripts>
        @livewireScripts
    </x-slot:scripts>
</x-ui::layouts.admin>
