@extends('layouts.web')

@section('title', 'Prestations et tarifs · Extensions de cils')
@section('meta_description', 'Extensions de cils à domicile : pose complète dès 65 €, remplissage, volume russe, dépose. Tarifs et prestations sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite(['resources/css/pages/prestations/index.css'])
@endsection

{{-- What this page adds to the site's graph: the whole catalogue, section by
     section, and the treatment it describes. The business comes from the
     layout and is named in `provider` by its `@id`, never copied out. --}}
@section('schema')
    @php
        $rubriques = [];

        foreach (config('tarifs.categories') as $slug => $categorie) {
            $offres = [[
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $categorie['pose']['nom'],
                    'description' => $categorie['description'],
                    'serviceType' => "Pose complète d'extensions de cils",
                ],
                'price' => (string) $categorie['pose']['prix'],
                'priceCurrency' => config('entreprise.devise'),
                'availability' => 'https://schema.org/InStock',
            ]];

            foreach ($categorie['remplissages'] as $remplissage) {
                $offres[] = [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $remplissage['nom'].' '.$categorie['nom'],
                        'description' => $remplissage['description'],
                        'serviceType' => "Remplissage d'extensions de cils",
                    ],
                    'price' => (string) $remplissage['prix'],
                    'priceCurrency' => config('entreprise.devise'),
                    'availability' => 'https://schema.org/InStock',
                ];
            }

            $rubriques[] = [
                '@type' => 'OfferCatalog',
                '@id' => url()->current().'#catalogue-'.$slug,
                'name' => $categorie['nom'],
                'description' => $categorie['description'],
                'itemListElement' => $offres,
            ];
        }

        $depose = config('tarifs.depose');

        $rubriques[] = [
            '@type' => 'OfferCatalog',
            '@id' => url()->current().'#catalogue-depose',
            'name' => 'Dépose',
            'itemListElement' => [[
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $depose['nom'],
                    'description' => $depose['description'],
                    'serviceType' => "Dépose d'extensions de cils",
                ],
                'price' => (string) $depose['prix'],
                'priceCurrency' => config('entreprise.devise'),
                'availability' => 'https://schema.org/InStock',
            ]],
        ];
    @endphp

    <x-seo.graph :nodes="[
        [
            '@type' => 'Service',
            '@id' => url()->current().'#prestation',
            'name' => 'Extensions de cils à domicile',
            'description' => 'Pose complète, remplissage et dépose d’extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose cil à cil, volume russe, volume mixte et volume intense par technicienne certifiée.',
            'url' => route('prestations'),
            'serviceType' => 'Extensions de cils',
            'termsOfService' => route('legal'),
            'providerMobility' => 'dynamic',
            'provider' => \App\Services\SiteGraphService::ref('business'),
            'areaServed' => \App\Services\SiteGraphService::areaServed(),
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                '@id' => url()->current().'#catalogue',
                'name' => 'Prestations extensions de cils à domicile',
                'itemListElement' => $rubriques,
            ],
        ],
        {{-- The catalogue tied back to the business: the treatment already
             names it through `provider`, and the direction is what counts. --}}
        [
            '@id' => \App\Services\SiteGraphService::id('business'),
            'hasOfferCatalog' => ['@id' => url()->current().'#catalogue'],
        ],
    ]" />
@endsection

@section('content')
    @include('web.prestations.partials.detail')
    @include('web.prestations.partials.tarifs')
    @include('web.prestations.partials.galerie')
@endsection
