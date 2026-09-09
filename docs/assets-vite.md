# Gestion des assets avec Vite

## Principe

Chaque page possède ses propres fichiers CSS et JS, importés individuellement via Vite. Les assets communs au layout (header, footer) sont importés directement depuis le layout.

## Les trois espaces

Au-dessus du découpage par page, le projet a **trois espaces**, chacun avec sa
paire d'entrées.

| Fichier | Ce qu'il porte | Entrée Vite ? |
|---|---|---|
| `resources/css/web.css` · `resources/js/web.js` | le site public | oui |
| `resources/css/admin.css` · `resources/js/admin.js` | le back-office | oui |
| `resources/css/app.css` · `resources/js/app.js` | le commun aux deux | **non** · les deux autres l'importent |

**Une entrée importe, elle ne contient pas.** Les règles vont dans un fichier
dédié sous `admin/` ou `web/`, enchaîné depuis l'entrée.

```css
/* resources/css/admin.css */
@import 'tailwindcss' source(none);

@import '../../vendor/falcon/ui-kit/resources/css/preset.css';
@import '../../vendor/falcon/booking/resources/css/booking-admin.css';
@import '../../vendor/falcon/analytics/resources/css/analytics-admin.css';

@source '../views/**/*.blade.php';

@import './app.css';
@import './admin/theme.css';
@import './admin/fields.css';
@import './admin/sidebar.css';
```

**Une page ne porte qu'une compilation Tailwind.** Deux feuilles écrivent les
mêmes noms de classes, et la dernière chargée gagne par sa seule position, sans
la moindre erreur. C'est pourquoi `app.css` ne porte pas `@import 'tailwindcss'`
et n'est pas déclaré dans `vite.config.js`.

Les fichiers par page décrits plus bas ne portent jamais Tailwind non plus · que
du CSS écrit à la main, dont les sélecteurs n'appartiennent qu'à nous.

`tests/Feature/Admin/OneStylesheetPerPageTest.php` tient ces invariants.

**Tailwind lit les commentaires comme le reste.** Il ne parse pas le code : il
ramasse dans les fichiers `@source` tout ce qui ressemble à un nom de classe, y
compris dans un `//`, un `/* */` ou un `{{-- --}}`. Deux conséquences, toutes
deux vécues au chantier 10 · une classe citée dans un commentaire entre dans la
feuille alors que rien ne l'emploie, et **retirer ce commentaire l'en fait
sortir**. Si une classe n'existe que dans du JavaScript ou dans une chaîne
construite, ne comptez pas sur un commentaire pour la maintenir en vie ·
déclarez la source, comme `web.css` le fait pour `../js/**/*.js`.

## Structure des fichiers

```
resources/
├── css/
│   ├── admin.css                 # Entree du back-office
│   ├── web.css                   # Entree du site public
│   ├── app.css                   # Le commun · importe par les deux
│   ├── admin/                    # Les regles du back-office
│   │   ├── theme.css
│   │   ├── fields.css
│   │   └── sidebar.css
│   ├── components/
│   │   └── layout/
│   │       ├── header.css        # Styles du header
│   │       └── footer.css        # Styles du footer
│   └── web/                      # Les regles du site public, et ses pages
│       ├── theme.css
│       ├── animations.css
│       ├── home/
│       │   ├── index.css         # Point d'entree CSS de la page d'accueil
│       │   ├── hero.css          # Styles de la section hero (optionnel)
│       │   └── features.css      # Styles d'une autre section (optionnel)
│       ├── about/
│       │   └── index.css
│       └── contact/
│           └── index.css
└── js/
    ├── admin.js                  # Entree du back-office
    ├── web.js                    # Entree du site public
    ├── app.js                    # Le commun · importe par les deux
    ├── admin/
    │   └── sidebar.js
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

Le glob de `vite.config.js` ne ramasse que `web/{page}/index.css` et
`web/{page}/index.js` · `web/theme.css` et `web/animations.css` sont importés
par `web.css`, pas compilés à part.

## Règles

### Un fichier `index.css` / `index.js` par page

Chaque page doit avoir au minimum un fichier `index.css` et/ou `index.js` dans son dossier sous `resources/css/web/{page}/` et `resources/js/web/{page}/`. Ce fichier est le **point d'entrée unique** de la page pour Vite.

### Segmentation optionnelle par section/composant

Si le CSS ou le JS d'une page devient trop volumineux, il est possible de le diviser en fichiers dédiés à chaque section ou composant de la page. Ces fichiers sont ensuite importés dans le `index.css` ou `index.js` correspondant.

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

Les fichiers CSS et JS des composants du layout se trouvent dans `resources/css/components/layout/` et `resources/js/components/layout/`. Ils sont importés directement dans le `<head>` du layout `web.blade.php` car ils sont communs à toutes les pages publiques.

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

Chaque vue principale de page inclut une seule directive `@vite` qui référence ses propres fichiers `index.css` et `index.js`.

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

Le layout doit prévoir une section `assets` dans le `<head>` pour accueillir ces directives :

```blade
{{-- resources/views/layouts/web.blade.php --}}
<head>
    @vite([
        'resources/css/components/layout/header.css',
        'resources/js/components/layout/header.js',
        'resources/css/components/layout/footer.css',
        'resources/js/components/layout/footer.js',
    ])

    @yield('assets')
</head>
```

## Configuration de Vite

Le fichier `vite.config.js` emploie `glob.sync`, du paquet npm `glob`, dans la propriété `input` du plugin Laravel : les points d'entrée `index.css` et `index.js` de chaque page sont détectés tout seuls. Les quatre entrées des espaces et les assets des composants du layout, eux, sont nommés à la main.

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { glob } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/web.css',
                'resources/js/web.js',
                'resources/css/admin.css',
                'resources/js/admin.js',

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
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

De cette manière, chaque nouveau dossier de page créé sous `resources/css/web/` ou `resources/js/web/` avec un fichier `index.css` / `index.js` sera automatiquement pris en charge par Vite sans modifier la configuration.

## Résumé

| Élément | Emplacement CSS | Emplacement JS | Import Vite |
|---|---|---|---|
| Page (ex: home) | `resources/css/web/home/index.css` | `resources/js/web/home/index.js` | `@vite` dans la vue de la page |
| Section de page (ex: hero) | `resources/css/web/home/hero.css` | `resources/js/web/home/hero.js` | `@import` dans `index.css` / `import` dans `index.js` |
| Composant layout (ex: header) | `resources/css/components/layout/header.css` | `resources/js/components/layout/header.js` | `@vite` dans le layout `web.blade.php` |
