@extends('layouts.web')

@section('title', 'À propos')
@section('meta_description', 'Découvrez l\'histoire et les valeurs de Chouchoute-toi. Technicienne certifiée en extensions de cils à domicile sur le bassin lémanique.')

@section('assets')
    @vite([
        'resources/css/web/about/index.css',
        'resources/js/web/about/index.js',
    ])
@endsection

@section('content')
    @include('web.about.partials.histoire')
    @include('web.about.partials.valeurs')
    @include('web.about.partials.pourquoi')
@endsection
