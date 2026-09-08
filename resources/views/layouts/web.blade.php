<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>@yield('title', 'Accueil') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">

    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', 'Accueil') · {{ config('app.name') }}">
    <meta property="og:description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Accueil') · {{ config('app.name') }}">
    <meta name="twitter:description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">
    <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/web.css', 'resources/js/web.js'])

    @vite([
        'resources/css/components/layout/header.css',
        'resources/js/components/layout/header.js',
        'resources/css/components/layout/footer.css',
        'resources/js/components/layout/footer.js',
    ])

    @yield('assets')

    @stack('head-extra')

    {{-- The layout always lays the site's graph: the business, the founder, the
         site and the current page. Pages add their own nodes only, and name the
         business through its `@id`.

         Never put this block behind `@hasSection('schema')`: a page laying that
         section would replace the layout's graph, and each page would then have
         to write the whole business out again. --}}
    @php
        $seoDescription = 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.';
    @endphp

    <x-seo.graph :nodes="\App\Services\SiteGraphService::siteNodes(
        $__env->yieldContent('title', 'Accueil').' · '.config('app.name'),
        $__env->yieldContent('meta_description', $seoDescription),
        $__env->yieldContent('schema_page_type', 'WebPage'),
    )" />

    {{-- What the page adds: its FAQ, its catalogue, its reviews. --}}
    @yield('schema')
</head>
<body class="bg-cream text-charcoal font-sans antialiased">
    <x-layout.header />

    <main>
        @yield('content')
    </main>

    <x-layout.footer />

    @stack('body-scripts')

    {{-- First-party audience measurement. Renders nothing while an admin is signed in. --}}
    {{-- The collector's configuration: the route name changes on every page,
         and tracking stops while an admin is signed in. Its code lives in
         resources/js/web.js and is compiled with the rest. --}}
    @analyticsConfig
</body>
</html>
