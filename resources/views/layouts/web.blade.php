<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>@yield('title', 'Accueil') — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', 'Accueil') — {{ config('app.name') }}">
    <meta property="og:description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Accueil') — {{ config('app.name') }}">
    <meta name="twitter:description" content="@yield('meta_description', 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose complète, remplissage, volume russe. Réservez votre séance.')">
    <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}">

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

    {{-- Stack optionnel (Livewire, etc.) --}}
    @stack('head-extra')

    {{-- JSON-LD Schema --}}
    @hasSection('schema')
        @yield('schema')
    @else
        <script type="application/ld+json">
        @php
        echo json_encode([
            "@context" => "https://schema.org",
            "@type" => "BeautySalon",
            "name" => "Chouchoute-toi by Amande",
            "description" => "Extensions de cils à domicile — pose cil à cil, volume russe et remplissage sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique.",
            "url" => url('/'),
            "telephone" => "+33671637666",
            "email" => "dc.amandine@gmail.com",
            "image" => asset('images/og-image.jpg'),
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "261 rue des Tattes",
                "addressLocality" => "Publier",
                "postalCode" => "74500",
                "addressRegion" => "Haute-Savoie",
                "addressCountry" => "FR",
            ],
            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => 46.3925,
                "longitude" => 6.5456,
            ],
            "openingHoursSpecification" => [
                [
                    "@type" => "OpeningHoursSpecification",
                    "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                    "opens" => "09:00",
                    "closes" => "17:00",
                ],
            ],
            "areaServed" => [
                ["@type" => "City", "name" => "Évian-les-Bains"],
                ["@type" => "City", "name" => "Thonon-les-Bains"],
                ["@type" => "City", "name" => "Publier"],
                ["@type" => "City", "name" => "Amphion"],
                ["@type" => "City", "name" => "Maxilly"],
                ["@type" => "City", "name" => "Neuvecelle"],
            ],
            "sameAs" => [
                "https://www.instagram.com/chouchoutetoibyamande/",
                "https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/",
            ],
            "priceRange" => "€€",
            "paymentAccepted" => "Espèces, Virement, Paiement mobile",
            "currenciesAccepted" => "EUR",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
        </script>
    @endif
</head>
<body class="bg-cream text-charcoal font-sans antialiased">
    <x-layout.header />

    <main>
        @yield('content')
    </main>

    <x-layout.footer />

    @stack('body-scripts')
</body>
</html>
