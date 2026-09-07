# Le back-office

Le back-office est une seule coquille, partagée par nos écrans et par ceux des
paquets `falcon/booking` et `falcon/analytics`. Cette page dit comment elle est
faite, et comment un écran de paquet vient s'y accrocher.

Pour les assets de l'espace, voir [Gestion des assets avec Vite](assets-vite.md).
Pour le site public, voir [Structure des fichiers](structure-fichiers.md).

## Trois fichiers, et un seul est la coquille

| Fichier | Rôle |
|---|---|
| `resources/views/components/layout/admin.blade.php` | **la coquille** · tout le chrome |
| `resources/views/layouts/booking-admin.blade.php` | l'adaptateur de `falcon/booking` |
| `resources/views/layouts/analytics-admin.blade.php` | l'adaptateur de `falcon/analytics` |

Les deux adaptateurs font trois lignes chacun. Tout le reste est dans la
coquille.

## La coquille

C'est un composant Blade, appelé `<x-layout.admin>`. Elle porte la barre
latérale, l'en-tête, le menu utilisateur, le thème sombre, les notifications, et
surtout **le seul `@vite` de l'espace** ·

```blade
@vite(['resources/css/admin.css', 'resources/js/admin.js'])
```

Une page ne porte qu'une feuille Tailwind, et c'est celle-là. Les paquets ne
compilent rien : ils déclarent leurs vues et leurs règles, et notre build
produit une feuille unique. Le détail est dans [assets-vite.md](assets-vite.md).

Elle accepte deux props ·

| Prop | Défaut | Ce qu'elle fait |
|---|---|---|
| `title` | `'Administration'` | le titre de l'onglet et le libellé de la barre du haut |
| `wide` | `false` | retire la largeur maximale et les marges du contenu |

`wide` existe pour le planning. Une colonne centrée convient à un formulaire ou
à une liste, qu'on lit sur une ligne courte ; elle est fausse pour une grille
temporelle, où elle laisse un tiers de l'écran vide alors que chaque colonne de
jour gagne à respirer.

### Nos propres écrans l'appellent directement

`resources/views/admin/dashboard.blade.php`, `admin/profile.blade.php` et
`admin/auth/login.blade.php` écrivent simplement ·

```blade
<x-layout.admin title="Tableau de bord">
    {{-- le contenu de l'écran --}}
</x-layout.admin>
```

Aucun adaptateur là-dedans. Les adaptateurs n'existent que pour les paquets, et
la raison suit.

## Pourquoi les paquets ont besoin d'un adaptateur

Un paquet ne connaît pas nos composants. Il rend une **vue mince** par écran,
qui étend un gabarit dont il ne sait que le nom, lu dans sa configuration ·

```blade
{{-- vendor/falcon/analytics/resources/views/dashboard/overview.blade.php --}}
@extends($analyticsLayout)

@section($analyticsSection)
    <livewire:analytics-overview />
@endsection
```

Il attend donc une vue extensible par `@extends`. Nous offrons un composant.
L'adaptateur fait la jonction, et il ne fait que ça ·

```blade
{{-- resources/views/layouts/analytics-admin.blade.php --}}
<x-layout.admin :title="$analyticsTitle ?? 'Analytics'">
    @yield('content')
</x-layout.admin>
```

Le contrat tient en deux lignes · **le paquet écrit dans une section, l'hôte la
rend dans le slot du composant.**

## Les clés qui nomment les adaptateurs

| Configuration | Valeur | Écrans concernés |
|---|---|---|
| `booking.admin.layout` | `layouts.booking-admin` | agenda, prestations, horaires, réglages |
| `analytics.dashboard.layout` | `layouts.analytics-admin` | vue d'ensemble, visiteurs, sessions, tunnels… |
| `analytics.marketing.layout` | `layouts.analytics-admin` | campagnes, pubs |

Chaque bloc porte aussi une clé `layout_section`, à `content` partout. Elle est
écrite plutôt que laissée au défaut parce qu'**un écran rendu dans la mauvaise
section paraît vide sans lever la moindre erreur** — Blade rend une section
absente comme une chaîne vide, et rien ne le signale.

Les deux modules d'analytics ont leur propre clé : rien n'oblige à leur donner
le même gabarit, ni même à monter les deux.

## Pourquoi deux adaptateurs et pas un

Ils se ressemblent au point qu'on est tenté de les fusionner. À ne pas faire ·

- **le nom de la variable de titre appartient au paquet.** `$bookingTitle` et
  `$analyticsTitle` sont deux contrats distincts, chacun décidé par son paquet ;
- **booking passe `wide`, analytics n'a pas cette notion.** Le contrôleur de
  l'agenda pose `bookingWide => true`, et lui seul ;
- un fichier unique accumulerait un `?? $xTitre` **par paquet installé** ;
- le nom du fichier dit quel paquet il sert.

Trois lignes dupliquées coûtent moins cher qu'un fichier qui grossit à chaque
paquet.

## Brancher un troisième paquet

1. Créer `resources/views/layouts/<paquet>-admin.blade.php` ·

   ```blade
   <x-layout.admin :title="$monPaquetTitre ?? 'Mon paquet'">
       @yield('content')
   </x-layout.admin>
   ```

2. Nommer ce gabarit dans la configuration du paquet, avec sa section.
3. Ajouter son entrée dans la barre latérale de la coquille.
4. Ajouter la ligne d'import du paquet dans `resources/css/admin.css` et
   `resources/js/admin.js` — son installateur le propose.

Puis `npm run build`, sans quoi les écrans du paquet sortent sans style : notre
feuille a été fabriquée en lisant ses vues telles qu'elles étaient au dernier
build.

## Le piège à connaître

Après tout changement d'un gabarit, d'un adaptateur ou d'un composant Blade
d'un paquet, lancer ·

```bash
php artisan view:cache
```

Sur Windows, un simple `view:clear` suffit à faire tomber la page d'analytics
avec `rename(...) : Accès refusé`. Ses blocs sont différés, donc plusieurs
requêtes arrivent en même temps et compilent les mêmes vues ; deux `rename()`
vers le même fichier se refusent mutuellement. `view:cache` compile tout en un
seul processus, et la course n'a pas lieu.
