<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>@yield('title', 'Accueil') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', 'Accueil') · {{ config('app.name') }}">
    <meta property="og:description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Accueil') · {{ config('app.name') }}">
    <meta name="twitter:description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">
    <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

    {{-- LA feuille et LE script de cet espace · une page, une feuille Tailwind --}}
    @vite(['resources/css/web.css', 'resources/js/web.js'])

    {{-- Assets communs du layout --}}
    @vite([
        'resources/css/components/layout/header.css',
        'resources/js/components/layout/header.js',
        'resources/css/components/layout/footer.css',
        'resources/js/components/layout/footer.js',
    ])

    {{-- Assets specifiques a la page --}}
    @yield('assets')

    {{-- Stack optionnel (Livewire, etc.) --}}
    @stack('head-extra')

    {{-- ── Donnees structurees ─────────────────────────────────────────────

         Le gabarit pose **toujours** le graphe du site · l'entreprise, la
         fondatrice, le site et la page courante. Les pages n'ajoutent que
         leurs propres nœuds, et designent l'entreprise par son `@id`.

         Ce bloc etait sous `@hasSection('schema')` · une page qui posait la
         section **remplacait** celui du gabarit, ce qui obligeait chacune a
         reecrire l'entreprise entiere. Six copies, aucun `@id`, et un moteur
         qui y lisait six entreprises sans rapport.

         Le titre et la description viennent des memes sections que le `<title>`
         et la balise `description` · une page se nomme une fois. --}}
    @php
        $seoDescription = 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.';
    @endphp

    <x-seo.graph :nodes="\App\Services\SiteGraphService::siteNodes(
        $__env->yieldContent('title', 'Accueil').' · '.config('app.name'),
        $__env->yieldContent('meta_description', $seoDescription),
        $__env->yieldContent('schema_page_type', 'WebPage'),
    )" />

    {{-- Ce que la page ajoute · sa FAQ, son catalogue, ses avis. --}}
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
    {{-- La configuration du collecteur · le nom de la route change à chaque
         page, et le suivi se coupe quand nous sommes connectée. Son code, lui,
         est dans resources/js/web.js et compilé avec le reste. --}}
    @analyticsConfig
</body>
</html>
