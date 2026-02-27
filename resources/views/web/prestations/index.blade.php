@extends('layouts.web')

@section('title', 'Mes prestations et tarifs')
@section('meta_description', 'Pose complète, remplissage, volume russe, dépose. Découvrez mes extensions de cils à domicile et mes tarifs sur Évian, Thonon et alentours.')

@section('assets')
    @vite([
        'resources/css/web/prestations/index.css',
        'resources/js/web/prestations/index.js',
    ])
@endsection

@section('content')
    @include('web.prestations.partials.detail')
    @include('web.prestations.partials.tarifs')
    @include('web.prestations.partials.galerie')
@endsection
