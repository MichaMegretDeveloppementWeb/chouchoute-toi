@extends('layouts.web')

@section('title', 'Avis clients · Extensions de cils à domicile')
@section('meta_description', 'Avis Google de nos clientes sur leurs extensions de cils à domicile. Pose cil à cil, volume russe, remplissage sur Évian et Thonon-les-Bains.')

@section('assets')
    @vite([
        'resources/css/web/reviews/index.css',
        'resources/js/web/reviews/index.js',
    ])
@endsection

{{-- ── Ce que cette page ajoute au graphe du site ───────────────────────────

     La note et les avis, ajoutes a l'entreprise que le gabarit declare. Cette
     page reecrivait un `BeautySalon` complet pour les y accrocher.

     **Rien n'est ecrit quand il n'y a rien** · une note absente vaut mieux
     qu'une note inventee, et Google refuse un `aggregateRating` sans avis. --}}
@section('schema')
    @php
        $entreprise = ['@id' => \App\Services\SiteGraphService::id('business')];

        if (! empty($googleRating) && ! empty($googleTotal)) {
            $entreprise['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $googleRating,
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => (string) $googleTotal,
            ];
        }

        foreach ($googleReviews ?? [] as $avis) {
            $entreprise['review'][] = [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $avis['nom']],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (string) $avis['note'],
                    'bestRating' => '5',
                    'worstRating' => '1',
                ],
                'reviewBody' => $avis['texte'],
                'itemReviewed' => ['@id' => \App\Services\SiteGraphService::id('business')],
            ];
        }
    @endphp

    <x-seo.graph :nodes="[$entreprise]" />
@endsection

@section('content')
    @include('web.reviews.partials.temoignages', [
        'googleReviews' => $googleReviews ?? [],
        'googleRating' => $googleRating ?? null,
        'googleTotal' => $googleTotal ?? null,
    ])
    @include('web.reviews.partials.confiance')
@endsection
