@extends('layouts.web')

@section('title', 'Avis clients')
@section('meta_description', 'Découvrez les avis de mes clientes sur leurs extensions de cils à domicile. 98% de satisfaction sur Évian, Thonon et alentours.')

@section('assets')
    @vite([
        'resources/css/web/reviews/index.css',
        'resources/js/web/reviews/index.js',
    ])
@endsection

@section('content')
    @include('web.reviews.partials.temoignages', [
        'googleReviews' => $googleReviews ?? [],
        'googleRating' => $googleRating ?? null,
        'googleTotal' => $googleTotal ?? null,
    ])
    @include('web.reviews.partials.confiance')
@endsection
