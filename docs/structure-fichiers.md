# Structure des fichiers

## Principe

Le projet suit une architecture segmentee : chaque page a son propre dossier de vues, ses propres assets, et son propre controller. Les pages sont decoupees en partials pour la lisibilite, et les elements communs (header, footer) sont de vrais composants Blade inclus dans un layout partage.

## Arborescence generale

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
│   ├── components/
│   │   └── layout/
│   │       ├── header.css
│   │       └── footer.css
│   └── web/
│       ├── home/
│       │   ├── index.css
│       │   └── hero.css          # Optionnel : style d'une section
│       ├── about/
│       │   └── index.css
│       └── contact/
│           └── index.css
├── js/
│   ├── components/
│   │   └── layout/
│   │       ├── header.js
│   │       └── footer.js
│   └── web/
│       ├── home/
│       │   ├── index.js
│       │   └── hero.js
│       ├── about/
│       │   └── index.js
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

Chaque route appelle un controller dedie.

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

Le layout `web.blade.php` fournit la structure HTML commune a toutes les pages publiques. Il inclut les composants Blade du header et du footer, et prevoit des sections pour les assets et le contenu de chaque page.

```blade
{{-- resources/views/layouts/web.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>

    {{-- Assets communs du layout --}}
    @vite([
        'resources/css/components/layout/header.css',
        'resources/js/components/layout/header.js',
        'resources/css/components/layout/footer.css',
        'resources/js/components/layout/footer.js',
    ])

    {{-- Assets specifiques a la page --}}
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

Le header et le footer sont de vrais composants Blade, places dans `resources/views/components/layout/`. Ils sont appeles dans le layout via la syntaxe `<x-layout.header />` et `<x-layout.footer />`.

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

Chaque page a une vue principale `index.blade.php` qui etend le layout et importe ses propres assets.

```blade
{{-- resources/views/web/home/index.blade.php --}}
@extends('layouts.web')

@section('title', 'Accueil')

@section('assets')
    @vite([
        'resources/css/web/home/index.css',
        'resources/js/web/home/index.js',
    ])
@endsection

@section('content')
    @include('web.home.partials.hero')
    @include('web.home.partials.features')
@endsection
```

## Partials

Les partials sont des fragments de vue specifiques a une page. Ils se trouvent dans le sous-dossier `partials/` de la page correspondante et sont inclus via `@include`.

```blade
{{-- resources/views/web/home/partials/hero.blade.php --}}
<section class="hero">
    {{-- Contenu du hero --}}
</section>
```

Les partials ne sont **pas** des composants Blade (pas de classe PHP associee). Ce sont de simples fichiers Blade inclus pour segmenter une page volumineuse.

## Resume des conventions

| Element | Emplacement | Inclusion |
|---|---|---|
| Layout web | `views/layouts/web.blade.php` | `@extends('layouts.web')` |
| Composant header | `views/components/layout/header.blade.php` | `<x-layout.header />` |
| Composant footer | `views/components/layout/footer.blade.php` | `<x-layout.footer />` |
| Vue principale de page | `views/web/{page}/index.blade.php` | Rendue par le controller |
| Partial de page | `views/web/{page}/partials/{section}.blade.php` | `@include('web.{page}.partials.{section}')` |
| Controller de page | `Http/Controllers/Web/{Page}Controller.php` | Route dans `web.php` |
| CSS de page | `css/web/{page}/index.css` | `@vite` dans la vue |
| JS de page | `js/web/{page}/index.js` | `@vite` dans la vue |
| CSS de section | `css/web/{page}/{section}.css` | `@import` dans `index.css` |
| JS de section | `js/web/{page}/{section}.js` | `import` dans `index.js` |
| CSS de composant layout | `css/components/layout/{composant}.css` | `@vite` dans le layout |
| JS de composant layout | `js/components/layout/{composant}.js` | `@vite` dans le layout |
