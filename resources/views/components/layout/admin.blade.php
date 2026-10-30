{{--
    Le back-office · la coquille du kit, et notre navigation dedans.

    Le kit écrit le document, les deux piles d'assets, la classe du thème, la
    barre et le conteneur des notifications. Ce fichier n'apporte que ce qui est
    à nous : la marque, l'arborescence des écrans et le menu du compte.

    **Il ne décide d'aucune largeur.** L'espace à côté de la barre est rendu tel
    quel, et chaque écran — le nôtre comme celui d'un paquet — se cadre sur son
    propre conteneur. Une propriété `wide` a tenu ce rôle ici, et elle enfermait
    par défaut tout ce qui ne demandait rien.
--}}
@props(['title' => 'Administration'])

@php
    // Les noms de routes des paquets sont fixes et s'écrivent en clair · seules
    // leurs adresses se règlent.
    $analytics = 'analytics.admin';
    $marketing = 'analytics.admin.marketing';
    $booking = 'booking.admin.';
    $admin = auth()->guard('admin')->user();

    // Les messages que booking n'a pas pu envoyer · leur nombre en rouge à
    // côté d'« Envois », que son événement met à jour sans recharger la page.
    $notSent = app(\Falcon\Booking\Repositories\Messages\UndeliveredMessageRepository::class);

    // Les écrans des réglages de booking · leur sous-menu s'allume sur eux, dans
    // l'agenda qui s'allume sur tout le paquet. Praticiens n'en est pas, bien que
    // son adresse soit sous `settings`.
    $onTheSettings = request()->routeIs(
        $booking.'settings',
        $booking.'schedule',
        $booking.'settings.slots',
        $booking.'settings.booking-window',
        $booking.'settings.notifications',
    );
@endphp

<x-ui::layouts.admin :title="$title">

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
        <x-ui::sidebar brand="Chouchoute-toi">
            {{-- Le dessin du kit, et une classe à nous · `admin/sidebar.css` le
                 recule sur l'axe des icônes. --}}
            <x-slot:logo>
                <img src="{{ asset('favicon/favicon.svg') }}" alt=""
                    class="admin-brand-logo h-8 w-8 shrink-0 rounded-lg bg-white object-contain ring-1 ring-black/5 dark:ring-white/10">
            </x-slot:logo>

            <x-ui::sidebar.group>
                <x-ui::sidebar.link
                    :href="route('admin.dashboard')"
                    icon="home"
                    :active="request()->routeIs('admin.dashboard')">
                    Tableau de bord
                </x-ui::sidebar.link>

                {{-- `href` : la première page de la section, où mène son icône
                     sur la barre repliée tant qu'on n'y est pas. --}}
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
                        :href="route($booking.'clients')"
                        :active="request()->routeIs($booking.'clients')">
                        Clients
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
                        :href="route($booking.'settings.practitioners')"
                        :active="request()->routeIs($booking.'settings.practitioners')">
                        Praticiens
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($booking.'journal')"
                        :active="request()->routeIs($booking.'journal')">
                        Journal
                    </x-ui::sidebar.link>

                    <x-ui::sidebar.link
                        :href="route($booking.'messages')"
                        :badge="$notSent->count()"
                        badge-color="red"
                        :badge-event="$notSent::SIDEBAR_EVENT"
                        :active="request()->routeIs($booking.'messages')">
                        Envois
                    </x-ui::sidebar.link>

                    {{-- Les réglages de l'agenda, et de lui seul · un sous-menu
                         à la fin de sa section, pour ne pas laisser croire
                         qu'ils règlent aussi l'audience ou le marketing. --}}
                    <x-ui::sidebar.collapsible
                        name="settings"
                        label="Réglages"
                        :href="route($booking.'settings')"
                        :active="$onTheSettings">
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
