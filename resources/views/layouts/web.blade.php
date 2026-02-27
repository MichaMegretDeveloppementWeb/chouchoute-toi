<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Accueil') — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Extensions de cils pose par pose à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance avec Chouchoute-toi.')">

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', 'Accueil') — {{ config('app.name') }}">
    <meta property="og:description" content="@yield('meta_description', 'Extensions de cils pose par pose à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique.')">
    <meta property="og:url" content="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

    {{-- CSS global (Tailwind + theme tokens) --}}
    @vite('resources/css/app.css')

    {{-- Assets communs du layout --}}
    @vite([
        'resources/css/components/layout/header.css',
        'resources/js/components/layout/header.js',
        'resources/css/components/layout/footer.css',
        'resources/js/components/layout/footer.js',
    ])

    {{-- Assets specifiques a la page --}}
    @yield('assets')

    {{-- Schema.org LocalBusiness --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "BeautySalon",
        "name": "Chouchoute-toi by Amande",
        "description": "Extensions de cils à domicile - pose cil à cil, volume russe, remplissage sur le bassin lémanique",
        "url": "https://chouchoute-toi.fr",
        "telephone": "+33671637666",
        "email": "dc.amandine@gmail.com",
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "261 rue des Tattes",
            "addressLocality": "Publier",
            "postalCode": "74500",
            "addressCountry": "FR"
        },
        "geo": {
            "@@type": "GeoCoordinates",
            "latitude": 46.3925,
            "longitude": 6.5456
        },
        "openingHours": "Mo-Fr 09:00-17:00",
        "areaServed": [
            "Thonon-les-Bains",
            "Évian-les-Bains",
            "Publier",
            "Amphion",
            "Maxilly",
            "Neuvecelle"
        ],
        "sameAs": [
            "https://www.instagram.com/chouchoutetoibyamande/",
            "https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/"
        ],
        "priceRange": "€€"
    }
    </script>
</head>
<body class="bg-cream text-charcoal font-sans antialiased">
    <x-layout.header />

    <main>
        @yield('content')
    </main>

    <x-layout.footer />
</body>
</html>
