# Gestion des assets avec Vite

## Principe

Chaque page possede ses propres fichiers CSS et JS, importes individuellement via Vite. Les assets communs au layout (header, footer) sont importes directement depuis le layout.

## Structure des fichiers

```
resources/
├── css/
│   ├── components/
│   │   └── layout/
│   │       ├── header.css        # Styles du header
│   │       └── footer.css        # Styles du footer
│   └── web/
│       ├── home/
│       │   ├── index.css         # Point d'entree CSS de la page d'accueil
│       │   ├── hero.css          # Styles de la section hero (optionnel)
│       │   └── features.css      # Styles d'une autre section (optionnel)
│       ├── about/
│       │   └── index.css
│       └── contact/
│           └── index.css
└── js/
    ├── components/
    │   └── layout/
    │       ├── header.js
    │       └── footer.js
    └── web/
        ├── home/
        │   ├── index.js          # Point d'entree JS de la page d'accueil
        │   └── hero.js           # JS de la section hero (optionnel)
        ├── about/
        │   └── index.js
        └── contact/
            └── index.js
```

## Regles

### Un fichier `index.css` / `index.js` par page

Chaque page doit avoir au minimum un fichier `index.css` et/ou `index.js` dans son dossier sous `resources/css/web/{page}/` et `resources/js/web/{page}/`. Ce fichier est le **point d'entree unique** de la page pour Vite.

### Segmentation optionnelle par section/composant

Si le CSS ou le JS d'une page devient trop volumineux, il est possible de le diviser en fichiers dedies a chaque section ou composant de la page. Ces fichiers sont ensuite importes dans le `index.css` ou `index.js` correspondant.

Exemple pour `resources/css/web/home/index.css` :

```css
@import './hero.css';
@import './features.css';
```

Exemple pour `resources/js/web/home/index.js` :

```js
import './hero.js';
import './slider.js';
```

### Assets du layout (header, footer)

Les fichiers CSS et JS des composants du layout se trouvent dans `resources/css/components/layout/` et `resources/js/components/layout/`. Ils sont importes directement dans le `<head>` du layout `web.blade.php` car ils sont communs a toutes les pages publiques.

```blade
{{-- resources/views/layouts/web.blade.php --}}
<head>
    @vite([
        'resources/css/components/layout/header.css',
        'resources/js/components/layout/header.js',
        'resources/css/components/layout/footer.css',
        'resources/js/components/layout/footer.js',
    ])
</head>
```

### Directive `@vite` dans les vues de page

Chaque vue principale de page inclut une seule directive `@vite` qui reference ses propres fichiers `index.css` et `index.js`.

```blade
{{-- resources/views/web/home/index.blade.php --}}
@extends('layouts.web')

@section('assets')
    @vite([
        'resources/css/web/home/index.css',
        'resources/js/web/home/index.js',
    ])
@endsection
```

Le layout doit prevoir une section `assets` dans le `<head>` pour accueillir ces directives :

```blade
{{-- resources/views/layouts/web.blade.php --}}
<head>
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
```

## Configuration de Vite

Le fichier `vite.config.js` utilise `import.meta.glob` via la propriete `input` du plugin Laravel pour detecter automatiquement tous les points d'entree `index.css` et `index.js` des pages, ainsi que les assets des composants du layout.

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { glob } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                ...glob.sync('resources/css/web/*/index.css'),
                ...glob.sync('resources/js/web/*/index.js'),
                'resources/css/components/layout/header.css',
                'resources/css/components/layout/footer.css',
                'resources/js/components/layout/header.js',
                'resources/js/components/layout/footer.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

De cette maniere, chaque nouveau dossier de page cree sous `resources/css/web/` ou `resources/js/web/` avec un fichier `index.css` / `index.js` sera automatiquement pris en charge par Vite sans modifier la configuration.

## Resume

| Element | Emplacement CSS | Emplacement JS | Import Vite |
|---|---|---|---|
| Page (ex: home) | `resources/css/web/home/index.css` | `resources/js/web/home/index.js` | `@vite` dans la vue de la page |
| Section de page (ex: hero) | `resources/css/web/home/hero.css` | `resources/js/web/home/hero.js` | `@import` dans `index.css` / `import` dans `index.js` |
| Composant layout (ex: header) | `resources/css/components/layout/header.css` | `resources/js/components/layout/header.js` | `@vite` dans le layout `web.blade.php` |
