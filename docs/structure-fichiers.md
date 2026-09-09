# Structure des fichiers

## Principe

Le projet suit une architecture segmentée : chaque page a son propre dossier de vues, ses propres assets, et son propre controller. Les pages sont découpées en partials pour la lisibilité, et les éléments communs (header, footer) sont de vrais composants Blade inclus dans un layout partagé.

Cette page décrit le **site public**. Le back-office suit d'autres règles, parce qu'il héberge aussi les écrans de `falcon/booking` et de `falcon/analytics` : voir [Le back-office](back-office.md).

## Arborescence générale

```
app/
└── Http/
    └── Controllers/
        └── Web/
            ├── HomeController.php
            ├── AboutController.php
            └── ContactController.php

resources/
├── css/
│   ├── web.css                   # Entree du site public
│   ├── web/                      # Les regles du site entier
│   │   ├── theme.css
│   │   └── header.css
│   ├── components/               # Ce que plusieurs pages partagent
│   │   └── accordion.css
│   └── pages/
│       ├── home/
│       │   ├── index.css
│       │   └── hero.css          # Optionnel : style d'une section
│       ├── about/
│       │   └── index.css
│       └── contact/
│           └── index.css
├── js/
│   ├── web.js                    # Entree du site public
│   ├── web/
│   │   ├── animations.js
│   │   └── header.js
│   ├── components/
│   │   └── accordion.js
│   └── pages/
│       ├── home/
│       │   ├── index.js
│       │   └── hero.js
│       └── contact/
│           └── index.js
└── views/
    ├── layouts/
    │   └── web.blade.php         # Layout commun aux pages publiques
    ├── components/
    │   └── layout/
    │       ├── header.blade.php  # Composant Blade <x-layout.header />
    │       └── footer.blade.php  # Composant Blade <x-layout.footer />
    └── web/
        ├── home/
        │   ├── index.blade.php   # Vue principale de la page d'accueil
        │   └── partials/
        │       ├── hero.blade.php
        │       └── features.blade.php
        ├── about/
        │   ├── index.blade.php
        │   └── partials/
        │       └── intro.blade.php
        └── contact/
            ├── index.blade.php
            └── partials/
                └── form.blade.php

routes/
└── web.php
```

## Routes

Chaque route appelle un controller dédié.

```php
// routes/web.php

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\ContactController;

Route::get('/', HomeController::class)->name('home');
Route::get('/a-propos', AboutController::class)->name('about');
Route::get('/contact', ContactController::class)->name('contact');
```

## Controllers

Les controllers publics se trouvent dans `app/Http/Controllers/Web/`. Pour les pages simples (une seule action), on utilise un controller invocable (`__invoke`).

```php
// app/Http/Controllers/Web/HomeController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('web.home.index');
    }
}
```

## Layout

Le layout `web.blade.php` fournit la structure HTML commune à toutes les pages publiques. Il inclut les composants Blade du header et du footer, et prévoit des sections pour les assets et le contenu de chaque page.

```blade
{{-- resources/views/layouts/web.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>

    @vite(['resources/css/web.css', 'resources/js/web.js'])

    @yield('assets')
</head>
<body>
    <x-layout.header />

    <main>
        @yield('content')
    </main>

    <x-layout.footer />
</body>
</html>
```

## Composants Blade (header, footer)

Le header et le footer sont de vrais composants Blade, placés dans `resources/views/components/layout/`. Ils sont appelés dans le layout via la syntaxe `<x-layout.header />` et `<x-layout.footer />`.

```blade
{{-- resources/views/components/layout/header.blade.php --}}
<header>
    <nav>
        {{-- Navigation --}}
    </nav>
</header>
```

```blade
{{-- resources/views/components/layout/footer.blade.php --}}
<footer>
    {{-- Contenu du footer --}}
</footer>
```

## Vues de page

Chaque page a une vue principale `index.blade.php` qui étend le layout et importe ses propres assets.

```blade
{{-- resources/views/web/home/index.blade.php --}}
@extends('layouts.web')

@section('title', 'Accueil')

@section('assets')
    @vite([
        'resources/css/pages/home/index.css',
        'resources/js/pages/home/index.js',
    ])
@endsection

@section('content')
    @include('web.home.partials.hero')
    @include('web.home.partials.features')
@endsection
```

## Partials

Les partials sont des fragments de vue spécifiques à une page. Ils se trouvent dans le sous-dossier `partials/` de la page correspondante et sont inclus via `@include`.

```blade
{{-- resources/views/web/home/partials/hero.blade.php --}}
<section class="hero">
    {{-- Contenu du hero --}}
</section>
```

Les partials ne sont **pas** des composants Blade (pas de classe PHP associée). Ce sont de simples fichiers Blade inclus pour segmenter une page volumineuse.

## Résumé des conventions

| Élément | Emplacement | Inclusion |
|---|---|---|
| Layout web | `views/layouts/web.blade.php` | `@extends('layouts.web')` |
| Composant header | `views/components/layout/header.blade.php` | `<x-layout.header />` |
| Composant footer | `views/components/layout/footer.blade.php` | `<x-layout.footer />` |
| Vue principale de page | `views/web/{page}/index.blade.php` | Rendue par le controller |
| Partial de page | `views/web/{page}/partials/{section}.blade.php` | `@include('web.{page}.partials.{section}')` |
| Controller de page | `Http/Controllers/Web/{Page}Controller.php` | Route dans `web.php` |
| CSS de page | `css/pages/{page}/index.css` | `@vite` dans la vue |
| JS de page | `js/pages/{page}/index.js` | `@vite` dans la vue |
| CSS de section | `css/pages/{page}/{section}.css` | `@import` dans `index.css` |
| JS de section | `js/pages/{page}/{section}.js` | `import` dans `index.js` |
| CSS du site entier | `css/web/{sujet}.css` | `@import` dans `web.css` |
| JS du site entier | `js/web/{sujet}.js` | `import` dans `web.js` |
| Composant partagé | `css/components/{nom}.css` · `js/components/{nom}.js` | `@import` / `import` dans l'`index` des pages concernées |
