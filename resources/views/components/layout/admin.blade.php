{{--
    The back-office · the kit's shell, and our navigation inside it.

    The kit writes the document, the two asset stacks, the theme class, the bar
    and the notification container. This file brings only what is ours · the
    brand, the screens of the bar and the account menu.

    **It sets no width.** The room beside the bar is given as it is, and each
    screen, ours as a package's, frames itself on its own container.
--}}
@props(['title' => 'Administration'])

@php
    $admin = auth()->guard('admin')->user();
    $screens = app(\App\Services\Admin\Navigation\OpenScreensService::class);

    // What this account opens, section by section · a link or a section shows
    // only when it leads somewhere, and a section leads to its first open screen.
    $bar = $screens->screensOpenTo($admin instanceof \App\Models\Admin ? $admin : null);
    $agendaOpens = [...$bar['agenda'], ...$bar['settings']];

    // The messages booking could not send · counted once, for whoever opens Envois.
    $sendings = 'booking.admin.messages';
    $notSent = \Falcon\Booking\Repositories\Messages\UndeliveredMessageRepository::class;
    $notSentCount = in_array($sendings, array_column($bar['agenda'], 'route'), true) ? app($notSent)->count() : null;
@endphp

<x-ui::layouts.admin :title="$title">

    <x-slot:head>
        {{-- A private surface, kept away from search engines. --}}
        <meta name="robots" content="noindex, nofollow">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">

        {{-- Ours after the kit's and the packages', so ours win. --}}
        @vite(['resources/css/admin.css', 'resources/js/admin.js'])

        @livewireStyles
    </x-slot:head>

    <x-slot:sidebar>
        {{-- A section lights on the prefix of its routes, never on the links it shows. --}}
        <x-ui::sidebar brand="Chouchoute-toi">
            {{-- The kit's drawing, and a class of ours that sets it on the axis of the icons. --}}
            <x-slot:logo>
                <img src="{{ asset('favicon/favicon.svg') }}" alt=""
                    class="admin-brand-logo h-8 w-8 shrink-0 rounded-lg bg-white object-contain ring-1 ring-black/5 dark:ring-white/10">
            </x-slot:logo>

            <x-ui::sidebar.group>
                @foreach ($bar['dashboard'] as $screen)
                    <x-ui::sidebar.link :href="route($screen['route'])" icon="home"
                        :active="request()->routeIs(...$screen['lights'])">
                        {{ $screen['title'] }}
                    </x-ui::sidebar.link>
                @endforeach

                {{-- Agenda --}}
                @if ($agendaOpens !== [])
                    <x-ui::sidebar.collapsible name="agenda" label="Agenda" icon="calendar-days"
                        :href="route($agendaOpens[0]['route'])"
                        :active="request()->routeIs('booking.admin.*')">
                        @foreach ($bar['agenda'] as $screen)
                            <x-ui::sidebar.link :href="route($screen['route'])"
                                :active="request()->routeIs(...$screen['lights'])"
                                :badge="$screen['route'] === $sendings ? $notSentCount : null"
                                badge-color="red"
                                :badge-event="$notSent::SIDEBAR_EVENT">
                                {{ $screen['title'] }}
                            </x-ui::sidebar.link>
                        @endforeach

                        {{-- The agenda's settings, last of its section, so they read as its own. --}}
                        @if ($bar['settings'] !== [])
                            <x-ui::sidebar.collapsible name="settings" label="Réglages"
                                :href="route($bar['settings'][0]['route'])"
                                :active="request()->routeIs(...$screens->lightsOf('settings'))">
                                @foreach ($bar['settings'] as $screen)
                                    <x-ui::sidebar.link :href="route($screen['route'])"
                                        :active="request()->routeIs(...$screen['lights'])">
                                        {{ $screen['title'] }}
                                    </x-ui::sidebar.link>
                                @endforeach
                            </x-ui::sidebar.collapsible>
                        @endif
                    </x-ui::sidebar.collapsible>
                @endif

                {{-- Audience --}}
                @if ($bar['audience'] !== [])
                    <x-ui::sidebar.collapsible name="audience" label="Audience" icon="chart-pie"
                        :href="route($bar['audience'][0]['route'])"
                        :active="request()->routeIs('analytics.admin.*') && ! request()->routeIs('analytics.admin.marketing.*')">
                        @foreach ($bar['audience'] as $screen)
                            {{-- Integrations has nothing to show until the Search Console is set up. --}}
                            @continue($screen['route'] === 'analytics.admin.integrations' && trim((string) config('analytics.search_console.client_id')) === '')

                            <x-ui::sidebar.link :href="route($screen['route'])"
                                :active="request()->routeIs(...$screen['lights'])">
                                {{ $screen['title'] }}
                            </x-ui::sidebar.link>
                        @endforeach
                    </x-ui::sidebar.collapsible>
                @endif

                {{-- Marketing --}}
                @if ($bar['marketing'] !== [])
                    <x-ui::sidebar.collapsible name="marketing" label="Marketing" icon="megaphone"
                        :href="route($bar['marketing'][0]['route'])"
                        :active="request()->routeIs('analytics.admin.marketing.*')">
                        @foreach ($bar['marketing'] as $screen)
                            <x-ui::sidebar.link :href="route($screen['route'])"
                                :active="request()->routeIs(...$screen['lights'])">
                                {{ $screen['title'] }}
                            </x-ui::sidebar.link>
                        @endforeach
                    </x-ui::sidebar.collapsible>
                @endif
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
