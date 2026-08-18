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

    {{-- L'etat de la barre laterale, pose avant la peinture pour la meme raison
         que le theme : lu apres, la barre changerait de largeur sous les yeux.

         Rien de retenu : repliee en dessous de 1500 px, ouverte au-dela. C'est
         la ou la barre ouverte coute un sixieme de la largeur a un portable, et
         ou elle ne coute plus rien a un grand ecran. Le choix n'est pas ecrit
         dans la memoire, pour qu'un autre ecran retrouve le sien. --}}
    <script>
        try {
            var barre = localStorage.getItem('fb-barre');

            document.documentElement.dataset.fbBarre = (barre === 'repliee' || barre === 'ouverte')
                ? barre
                : (window.innerWidth >= 1500 ? 'ouverte' : 'repliee');
        } catch (e) {
            document.documentElement.dataset.fbBarre = 'repliee';
        }
    </script>

    @vite([
        'resources/css/ui-kit.css',
        'resources/js/ui-kit.js',
        'packages/falcon-booking/resources/js/booking-admin.js',
    ])

    @livewireStyles
</head>
<body class="min-h-full antialiased">

    {{--
        Un seul groupe : les sections sont maintenant des sous-menus repliables,
        et l'etiquette de groupe ferait doublon avec leur libelle.

        L'etat actif d'une section se calcule sur le prefixe de ses routes, pas
        sur la liste de ses liens : « Integrations » n'apparait que si la Search
        Console est configuree, donc la section ne peut pas se deduire de ce
        qu'elle contient.
    --}}
    <x-ui.sidebar brand="Chouchoute-toi" :brand-logo="asset('favicon/favicon.svg')">
        <x-ui.sidebar.group>
            <x-ui.sidebar.link
                :href="route('admin.dashboard')"
                icon="home"
                :active="request()->routeIs('admin.dashboard')">
                Tableau de bord
            </x-ui.sidebar.link>

            <x-ui.sidebar.collapsible
                name="agenda"
                label="Agenda"
                icon="calendar-days"
                :active="request()->routeIs($booking.'*')">
                <x-ui.sidebar.link
                    :href="route($booking.'agenda')"
                    :active="request()->routeIs($booking.'agenda')">
                    Planning
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($booking.'catalogue')"
                    :active="request()->routeIs($booking.'catalogue')">
                    Prestations
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($booking.'categories')"
                    :active="request()->routeIs($booking.'categories')">
                    Catégories
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($booking.'journal')"
                    :active="request()->routeIs($booking.'journal')">
                    Journal
                </x-ui.sidebar.link>

                {{-- Un troisième niveau : les réglages sont une section, une page
                     par famille, et les horaires en font partie. --}}
                <x-ui.sidebar.collapsible
                    name="reglages"
                    label="Réglages"
                    :active="request()->routeIs($booking.'settings*') || request()->routeIs($booking.'schedule')">
                    <x-ui.sidebar.link
                        :href="route($booking.'settings')"
                        :active="request()->routeIs($booking.'settings')">
                        Établissement
                    </x-ui.sidebar.link>

                    <x-ui.sidebar.link
                        :href="route($booking.'schedule')"
                        :active="request()->routeIs($booking.'schedule')">
                        Horaires
                    </x-ui.sidebar.link>

                    <x-ui.sidebar.link
                        :href="route($booking.'settings.slots')"
                        :active="request()->routeIs($booking.'settings.slots')">
                        Créneaux
                    </x-ui.sidebar.link>

                    <x-ui.sidebar.link
                        :href="route($booking.'settings.booking-window')"
                        :active="request()->routeIs($booking.'settings.booking-window')">
                        Réservation
                    </x-ui.sidebar.link>

                    <x-ui.sidebar.link
                        :href="route($booking.'settings.clients')"
                        :active="request()->routeIs($booking.'settings.clients')">
                        Clients
                    </x-ui.sidebar.link>
                </x-ui.sidebar.collapsible>
            </x-ui.sidebar.collapsible>

            <x-ui.sidebar.collapsible
                name="audience"
                label="Audience"
                icon="chart-pie"
                :active="request()->routeIs($analytics.'.*')">
                <x-ui.sidebar.link
                    :href="route($analytics.'.overview')"
                    :active="request()->routeIs($analytics.'.overview')">
                    Vue d'ensemble
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($analytics.'.realtime')"
                    :active="request()->routeIs($analytics.'.realtime')">
                    Temps réel
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($analytics.'.visitors')"
                    :active="request()->routeIs($analytics.'.visitors*')">
                    Visiteurs
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($analytics.'.sessions')"
                    :active="request()->routeIs($analytics.'.sessions*')">
                    Sessions
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($analytics.'.events')"
                    :active="request()->routeIs($analytics.'.events')">
                    Événements
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($analytics.'.funnels')"
                    :active="request()->routeIs($analytics.'.funnels')">
                    Tunnels
                </x-ui.sidebar.link>

                @if (trim((string) config('analytics.search_console.client_id')) !== '')
                    <x-ui.sidebar.link
                        :href="route($analytics.'.integrations')"
                        :active="request()->routeIs($analytics.'.integrations*')">
                        Intégrations
                    </x-ui.sidebar.link>
                @endif
            </x-ui.sidebar.collapsible>

            <x-ui.sidebar.collapsible
                name="marketing"
                label="Marketing"
                icon="megaphone"
                :active="request()->routeIs($marketing.'.*')">
                <x-ui.sidebar.link
                    :href="route($marketing.'.dashboard')"
                    :active="request()->routeIs($marketing.'.dashboard')">
                    Vue d'ensemble
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($marketing.'.campaigns')"
                    :active="request()->routeIs($marketing.'.campaigns*')">
                    Campagnes
                </x-ui.sidebar.link>

                <x-ui.sidebar.link
                    :href="route($marketing.'.ads')"
                    :active="request()->routeIs($marketing.'.ads*')">
                    Publicités
                </x-ui.sidebar.link>
            </x-ui.sidebar.collapsible>
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

                {{-- Le pendant du declencheur mobile, pour l'autre bout de
                     l'echelle : au-dela de 1500 px la barre est ouverte d'office
                     et prend une largeur dont la grille du planning a besoin.
                     Entre les deux, la barre est deja un rail et il n'y a rien a
                     replier, d'ou `hidden wide:inline-flex`.

                     Le meme trait que le declencheur mobile : c'est le meme
                     geste, sur l'autre bout de l'echelle. Une fleche aurait dit
                     un sens, or ce bouton bascule.

                     Des `lg` et non des `wide` : le survol ne deploie plus la
                     barre, donc sans ce bouton les libelles seraient hors
                     d'atteinte entre 1024 et 1500 px. --}}
                <button type="button" onclick="basculerLaBarre()"
                    class="hidden rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 lg:inline-flex dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    aria-label="Replier ou deployer la barre laterale">
                    <x-ui.icon name="bars-3" class="h-5 w-5" />
                </button>

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
