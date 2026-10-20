{{--
    La page de réservation de booking, dans le site · notre en-tête, notre pied,
    notre police et nos couleurs autour de l'écran du paquet. Nommé par
    `booking.layouts.public`.

    `@falconStyles` passe avant notre feuille · l'ordre des couches se joue à la
    première feuille lue, et la nôtre, qui habille la page (`web/booking.css`),
    arrive après celle du paquet. Les deux directives de Livewire sont écrites,
    l'injection automatique étant coupée sur ce site · sans elles, la réservation
    s'affiche entière et ne répond à aucun clic.

    Le référencement de la page vient du paquet, qui le pose sur la pile `head` ·
    notre description de l'entreprise et du site s'y ajoute, dans un bloc à
    elle · le paquet les emprunte par leur `@id` (`seo.*_entity_id`, dans
    `config/booking.php`), et sur la page même, rien n'est cité sans être
    déclaré.

    Le corps fait au moins un écran, et l'écran du paquet s'étire · une page
    courte, la page introuvable, garde notre pied en bas.
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

    @falconStyles

    @vite(['resources/css/web.css', 'resources/js/web.js'])

    @livewireStyles

    @stack('head')

    <x-seo.graph :nodes="\App\Services\SiteGraphService::entityNodes()" />
</head>
<body class="flex min-h-dvh flex-col bg-cream text-charcoal font-sans antialiased">
    <x-layout.header />

    <main class="booking-page flex-1">
        {{ $slot }}
    </main>

    <x-layout.footer />

    @falconScripts

    @livewireScripts

    @analyticsCollector
</body>
</html>
