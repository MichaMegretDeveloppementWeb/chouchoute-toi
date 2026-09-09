# Gestion des assets avec Vite

## Principe

Deux entrées par espace, et une paire par page. L'entrée de l'espace porte tout
ce qui vaut pour toutes ses pages ; la paire d'une page ne porte que ce qui
n'appartient qu'à elle.

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

Quatre dossiers, et chacun répond à une question différente · `web/` et
`admin/` portent ce qui vaut pour tout un espace, `pages/` ce qui n'appartient
qu'à une page, `components/` ce que plusieurs pages partagent.

```
resources/
├── css/
│   ├── web.css                   # ENTREE · le site public
│   ├── admin.css                 # ENTREE · le back-office
│   ├── app.css                   # le commun aux deux, importe par les deux
│   │
│   ├── web/                      # les regles du SITE, importees par web.css
│   │   ├── theme.css             #   la police et les six couleurs de la marque
│   │   ├── animations.css        #   l'apparition au defilement
│   │   ├── anchors.css           #   le decalage des ancres sous l'en-tete fixe
│   │   └── header.css            #   l'en-tete, present sur toutes les pages
│   │
│   ├── admin/                    # les regles du BACK-OFFICE, importees par admin.css
│   │   ├── theme.css
│   │   ├── fields.css
│   │   └── sidebar.css
│   │
│   ├── components/               # partages, importes par les pages qui en ont besoin
│   │   ├── accordion.css
│   │   └── cta-banner.css
│   │
│   └── pages/                    # une page, un dossier · le glob ne lit que ca
│       ├── home/
│       │   ├── index.css         #   le point d'entree de la page
│       │   ├── hero.css          #   une section
│       │   └── avant-apres.css
│       ├── about/
│       ├── contact/
│       ├── prestations/
│       └── reviews/
│
└── js/
    ├── web.js                    # ENTREE · collecteur, animations, en-tete
    ├── admin.js                  # ENTREE
    ├── app.js                    # le commun aux deux
    │
    ├── web/
    │   ├── animations.js         #   l'observateur qui pose `is-visible`
    │   └── header.js
    │
    ├── admin/
    │   └── sidebar.js
    │
    ├── components/
    │   └── accordion.js          #   UN accordeon, pour les trois pages qui en ont un
    │
    └── pages/
        ├── home/
        │   ├── index.js
        │   └── avant-apres.js
        ├── contact/
        └── reviews/
```

**Le glob ne ramasse que `pages/*/index.css` et `pages/*/index.js`.** Un dossier
sous `pages/` **est** une page ; rien d'autre n'y vit. Les fichiers de `web/` et
d'`admin/` sont importés par leur entrée, jamais compilés à part.

## Règles

### Une paire par page, et seulement si elle sert

Une page a un `index.css` **et/ou** un `index.js` dans son dossier sous
`resources/css/pages/{page}/` et `resources/js/pages/{page}/`. Ce fichier est le
**point d'entrée unique** de la page pour Vite.

**Une page qui n'a ni CSS ni JS propre n'a pas de `@section('assets')`** —
`mentions-legales` est dans ce cas. **Aucun fichier vide** : un fichier de
section se crée le jour où il a quelque chose à porter, pas avant.

### Segmentation optionnelle par section

Si le CSS ou le JS d'une page devient volumineux, on le divise en fichiers
dédiés à chaque section, importés dans l'`index` correspondant.

```css
/* resources/css/pages/home/index.css */
@import './hero.css';
@import './avant-apres.css';
@import '../../components/accordion.css';
```

```js
/* resources/js/pages/home/index.js */
import './avant-apres.js';
import '../../components/accordion.js';
```

### Ce qui vaut pour tout le site va dans l'entrée

Un comportement ou un style présent sur **toutes** les pages n'est pas une page :
il va dans `web/`, importé par `web.css` ou `web.js`. C'est le cas de l'en-tête,
de l'observateur d'animations, du thème et des ancres.

> Ce n'était pas le cas jusqu'au 2026-09-09 · `header.css`, `header.js`,
> `footer.css` et `footer.js` étaient quatre entrées Vite déclarées à part et
> chargées par un second `@vite` dans le layout — sur exactement les mêmes pages
> que `web.css` et `web.js`. `footer.js` ne contenait d'ailleurs rien du pied de
> page, mais l'observateur de tout le site, et `footer.css` était vide.

### Ce que plusieurs pages partagent va dans `components/`

Un accordéon, une bannière d'appel à l'action. Le fichier vit dans
`components/`, et chaque page qui l'emploie l'importe depuis son `index`. Rien
n'entre dans l'entrée de l'espace tant que toutes les pages n'en ont pas besoin.

### La directive `@vite` dans les vues de page

Le layout charge l'entrée de l'espace ; la vue de page charge la sienne.

```blade
{{-- resources/views/web/home/index.blade.php --}}
@extends('layouts.web')

@section('assets')
    @vite([
        'resources/css/pages/home/index.css',
        'resources/js/pages/home/index.js',
    ])
@endsection
```

```blade
{{-- resources/views/layouts/web.blade.php --}}
<head>
    @vite(['resources/css/web.css', 'resources/js/web.js'])

    @yield('assets')
</head>
```

## Configuration de Vite

Le fichier `vite.config.js` emploie `glob.sync`, du paquet npm `glob`, dans la
propriété `input` du plugin Laravel : les points d'entrée de chaque page sont
détectés tout seuls. Les quatre entrées des espaces sont nommées à la main.

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

                ...glob.sync('resources/css/pages/*/index.css'),
                ...glob.sync('resources/js/pages/*/index.js'),
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

Un nouveau dossier sous `resources/css/pages/` ou `resources/js/pages/` avec un
`index` est pris en charge sans toucher à la configuration.

## Résumé

| Élément | Emplacement CSS | Emplacement JS | Import |
|---|---|---|---|
| Entrée de l'espace | `css/web.css` | `js/web.js` | `@vite` dans le layout |
| Règle du site entier | `css/web/{sujet}.css` | `js/web/{sujet}.js` | `@import` / `import` dans l'entrée |
| Page (ex : home) | `css/pages/home/index.css` | `js/pages/home/index.js` | `@vite` dans la vue de la page |
| Section de page (ex : hero) | `css/pages/home/hero.css` | `js/pages/home/hero.js` | `@import` / `import` dans l'`index` |
| Composant partagé (ex : accordéon) | `css/components/accordion.css` | `js/components/accordion.js` | `@import` / `import` dans l'`index` des pages concernées |
