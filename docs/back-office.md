# Le back-office

Le back-office est une seule coquille, partagée par nos écrans et par ceux des
paquets `falcon/booking` et `falcon/analytics`. Cette page dit comment elle est
faite, comment un écran de paquet vient s'y loger, et ce que l'espace exige de
sa configuration.

Pour les assets de l'espace, voir [Gestion des assets avec Vite](assets-vite.md).
Pour le site public, voir [Structure des fichiers](structure-fichiers.md).

## Deux composants

| Fichier | Rôle |
|---|---|
| `resources/views/components/layout/admin.blade.php` | **la coquille** · `<x-layout.admin>`, pour tous les écrans connectés |
| `resources/views/components/layout/admin-guest.blade.php` | la page de connexion · `<x-layout.admin-guest>`, sans barre latérale |

## La coquille

`<x-layout.admin>` monte la coquille du kit, `<x-ui::layouts.admin>`. **Le kit
écrit le document**, la classe du thème, la barre latérale, le conteneur des
notifications, et pose les feuilles et les scripts du kit et des paquets. Notre
composant n'apporte que ce qui est à nous ·

- la marque et l'arborescence des écrans, dans la barre ;
- le menu du compte, `<x-layout.admin-user-menu>` ;
- notre feuille et notre script, **après** ceux du kit et des paquets, donc
  ce sont les nôtres qui gagnent ·

  ```blade
  @vite(['resources/css/admin.css', 'resources/js/admin.js'])
  ```

- le refus d'indexation, et les marques de Livewire.

Elle accepte une prop ·

| Prop | Défaut | Ce qu'elle fait |
|---|---|---|
| `title` | `'Administration'` | le titre de l'onglet et le libellé de la barre du haut |

**Elle ne décide d'aucune largeur.** L'espace à côté de la barre est rendu tel
quel, et chaque écran se cadre sur son propre conteneur. Les nôtres se plafonnent
ainsi ·

```blade
<x-layout.admin title="Tableau de bord">
    <div class="mx-auto max-w-[90em] px-4 py-6 sm:px-6 sm:py-8">
        {{-- le contenu de l'écran --}}
    </div>
</x-layout.admin>
```

Le planning de booking, lui, prend toute la largeur sans rien demander · une
grille horaire perdrait un tiers de l'écran dans une colonne centrée.

## Les écrans des paquets s'y logent seuls

Chaque paquet lit dans sa configuration **le nom du composant** dans lequel
rendre ses écrans, et l'ouvre comme une balise autour de son contenu, avec son
titre. Il n'y a ni adaptateur, ni section à nommer.

| Configuration | Valeur | Écrans concernés |
|---|---|---|
| `booking.layouts.admin` | `layout.admin` | planning, prestations, catégories, journal, réglages |
| `booking.layouts.public` | `null` | la page publique de réservation garde la coquille du paquet · elle se suffit, et le site a ses propres pages |
| `analytics.layouts.admin` | `layout.admin` | audience **et** marketing · c'est la même administration, donc une seule clé |

La valeur s'écrit comme on écrirait la balise · `layout.admin` pour
`<x-layout.admin>`.

## Les assets de l'espace

`resources/css/admin.css` ouvre sur l'ordre des huit couches du kit, puis
Tailwind, puis les noms du kit (`bg-surface`, `text-muted`…) pour le HTML que
nous écrivons, puis nos propres règles. **Rien des paquets** · chacun compile et
livre sa feuille et son script, le site en publie une copie, et le kit les pose
dans les pages qu'il dessine ·

```bash
php artisan vendor:publish --tag=laravel-assets --force
```

à chaque mise à jour d'un paquet. `resources/js/admin.js` n'importe, de même,
que notre script commun.

## Le garde et la mesure d'audience

**Le back-office a son garde à lui, `admin`**, distinct de celui des clients.
C'est ce qui permet à analytics d'exclure le trafic interne sans toucher à celui
du public · `config/analytics.php` porte `exclude_guards => ['admin']`.

- **ne jamais mettre `web` dans `exclude_guards`** · le public cesserait d'être
  mesuré ;
- `subject_guards` vaut `[]` · l'espace client de booking s'ouvre par un lien
  envoyé par e-mail, pas par une connexion ;
- un nom de garde absent de `config/auth.php` est ignoré sans erreur ·
  `php artisan analytics:check` le signale ;
- la mesure se passe de consentement · adresses IP anonymisées
  (`anonymize_ip => true`), aucun cookie de mesure, donc aucun bandeau sur le
  site.

## Le premier compte

```bash
php artisan db:seed --class=AdminSeeder --force
```

Rejouable sans danger. Le mot de passe initial est `password` · le changer
aussitôt sur `/admin/profil`.

## La planification

Booking et analytics planifient leurs tâches eux-mêmes · **rien à écrire dans
`routes/console.php`**. En ligne, une tâche cron lance `schedule:run` chaque
minute, et sa sortie se lit dans l'interface de l'hébergeur · ne pas y ajouter
`>> /dev/null 2>&1`, qui ferait disparaître ce diagnostic.

## Brancher un troisième paquet

1. L'installer, puis publier ses fichiers compilés ·
   `php artisan vendor:publish --tag=laravel-assets --force`.
2. Nommer `layout.admin` comme coquille de son administration, dans la clé que
   sa documentation indique.
3. Ajouter ses entrées dans la barre latérale de `<x-layout.admin>` · ses noms
   de routes sont fixes, seules ses adresses se règlent.
4. Vider et reconstruire les vues compilées (section suivante), puis lancer
   `php artisan ui-kit:check` **et** le diagnostic du paquet · aucun des deux ne
   couvre l'autre.

Rien n'est à ajouter dans `admin.css` ni dans `admin.js`, et aucun
`npm run build` n'est nécessaire pour le paquet.

## Le piège à connaître

Après une mise à jour d'un paquet, ou toute modification d'un gabarit ou d'un
composant Blade, reconstruire les vues compilées d'un coup ·

```bash
php artisan view:clear && rm -f storage/framework/views/*.tmp
php artisan view:cache
```

Sur Windows, un simple `view:clear` suffit à faire tomber la page d'analytics
avec `rename(...) : Accès refusé`. Ses blocs se chargent en différé, donc
plusieurs requêtes arrivent en même temps et compilent les mêmes vues ; deux
`rename()` vers le même fichier se refusent mutuellement, et des `.tmp`
orphelins restent derrière. `view:cache` compile tout en un seul processus, et
la course n'a pas lieu.
