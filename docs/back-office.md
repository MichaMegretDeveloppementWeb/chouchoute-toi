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

- la marque et l'arborescence des écrans, dans la barre · Tableau de bord,
  puis quatre sections · **Agenda** (Planning, Clients, Prestations,
  Catégories, Praticiens, Journal), **Réglages** (Établissement, Horaires,
  Créneaux, Réservation, Notifications), **Audience** et **Marketing** ;
- le dessin de la barre, `resources/css/admin/sidebar.css` · des lignes sur
  toute sa largeur, un filet entre elles, la page courante pleine de la couleur
  d'action de booking · il vise les classes crochets du kit, dont il ne copie
  aucune vue ;
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

## Les comptes et les rôles

**Un compte a un rôle, et le code dit ce que chacun ouvre.** Les rôles sont dans
`app/Enums/Team/AdminRole.php`, les règles dans `app/Providers/GateServiceProvider.php`.
Les paquets nomment leurs capacités et les laissent ouvertes à tout compte connecté ·
ce fichier ne dit donc que ce qu'un praticien n'a pas.

| Rôle | Ce qu'il ouvre |
|---|---|
| **Propriétaire** | tout le back-office |
| **Praticien** | le Planning, où il voit tout et ne change que les rendez-vous où il réalise une prestation · les Clients, sans effacer ni télécharger les données · son Profil |

- **Un rôle neuf** · un cas dans l'énumération, et ses règles dans le fournisseur ;
- **`practitioner_id` dit quelle fiche est ce compte** · c'est ce qui fait qu'un
  rendez-vous est « le sien » ;
- **un compte suspendu n'est plus personne** · `suspended_at`, lu par
  `ActiveAdminUserProvider`, que le garde `admin` emploie · la connexion, une session
  déjà ouverte, le site et les deux paquets se ferment d'un coup, au clic suivant ;
- **`php artisan booking:abilities --user=3`** dit ce que chaque capacité de booking
  répond à ce compte · elle les pose **sans objet**, donc chaque règle du fournisseur
  accepte de n'en recevoir aucun ;
- **la barre ne montre que ce que le compte ouvre** · `app/Services/Team/OpenScreensService.php`
  tient la liste de ses écrans, section par section · chacun avec la capacité que sa
  route demande, son titre et ce qui allume son lien · la coquille la parcourt · une
  section sans écran ouvert disparaît, et son icône mène au premier qui l'est ·
  le nombre des envois en échec n'est lu qu'une fois, et seulement pour qui ouvre
  Envois · **un écran ajouté à la barre s'ajoute à cette liste, et à elle seule** ·
  un essai compare chaque capacité de la liste à celle que sa route demande ;
- **le tableau de bord** renvoie un compte qui ne l'ouvre pas vers la page de son rôle ;
- **un écran refusé se dit dans l'administration** · un refus 403 sous `/admin`, pour
  un compte connecté, rend `admin/forbidden` avec la barre et le retour à sa page
  (`bootstrap/app.php`) · ailleurs, et pour une demande de données, la réponse
  ordinaire du cadre reste · **un geste refusé sur un écran déjà ouvert** (le rôle a
  changé entre-temps) recharge la page, qui dit alors le refus de la même façon ·
  `resources/js/admin/refusals.js`, une fois · un second refus aussitôt garde la
  réponse de Livewire.

### Les comptes de l'équipe naissent dans la fiche

Le site remplit le contrat que booking lui tend pour l'équipe ·
`app/Services/Team/SiteTeamAccessService.php`, lié dans `AppServiceProvider`. Une fiche
de praticien porte donc une adresse, obligatoire, et la rangée « Accès ».

| Geste dans l'équipe | Ce qui arrive au compte |
|---|---|
| créer une fiche avec un accès | un compte à l'adresse de la fiche, sans mot de passe connu, et l'invitation part une fois la fiche enregistrée |
| une fiche à l'adresse d'un compte qu'aucune fiche ne nomme | ce compte est repris, avec son mot de passe, et rien ne part · c'est le cas du compte d'origine |
| corriger l'adresse d'une invitation en attente | le lien part à la nouvelle adresse, l'ancien ne sert plus |
| corriger l'adresse d'un compte en service | l'identifiant change, le mot de passe reste, et les deux adresses sont prévenues |
| « Renvoyer l'invitation » | un nouveau lien remplace le précédent · une minute entre deux envois, sauf si le précédent n'est pas parti |
| archiver, réactiver | le compte est suspendu, puis rendu tel qu'il était · un appareil resté connecté (« Rester connecté ») doit se reconnecter · une session ordinaire de moins de deux heures, elle, reprend |
| supprimer | le compte et ses liens partent avec la fiche |

**Le dernier propriétaire et le compte qui agit** ne sont jamais rétrogradés, suspendus
ni supprimés depuis l'équipe.

**Les liens** · l'invitation vit 7 jours (`admin_invitations`, table
`admin_invitation_tokens`), le mot de passe oublié 60 minutes (`admins`) · chacun ne
sert qu'une fois. Le lien nomme le compte par son numéro, jamais par son adresse, et se
bâtit sur `APP_URL`, jamais sur l'adresse de la requête · **`APP_URL` doit donc être
celle du site en ligne**, ou les liens mèneront ailleurs.

**Les écrans** · `/admin/bienvenue/{compte}/{jeton}`, `/admin/mot-de-passe-oublie` et
`/admin/mot-de-passe/{compte}/{jeton}`. La réponse au mot de passe oublié est la même
qu'un compte existe ou non. Une fois le mot de passe choisi, le compte est connecté et
arrive sur la page de son rôle · le tableau de bord pour un propriétaire, le Planning
pour un praticien.

**Les messages** partent de `app/Mail/Team/` et de la vue `mail/team/message` ·
`MAIL_FROM_ADDRESS` les signe. Un envoi qui échoue s'écrit au journal, et la liste de
l'équipe le dit à la place de l'invitation.

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

## Ce qu'analytics garde

Les écrans proposent 7, 30 et 90 jours et les 12 derniers mois, et s'ouvrent
sur 30 jours · `periods` et `default_period` dans `config/analytics.php`.
**Le paquet garde le détail deux fois la plus longue période**, soit 730 jours,
sous les vingt-cinq mois que la CNIL admet pour une mesure d'audience sans
bandeau. Au-delà, tout disparaît. `php artisan analytics:check` dit la durée en
vigueur, au point « Périodes ».

Allonger une période garde davantage, aussitôt. En raccourcir une n'efface rien seule · le diagnostic dit que la réduction
attend, et `php artisan analytics:shorten` l'applique.

**`config/analytics.php` est le fichier du paquet**, à six choix près · les deux
gardes ci-dessus, la coquille `layout.admin`, `auth:admin` sur les deux groupes
d'écrans, et les périodes. Pour relire ses commentaires à jour après une version
du paquet, le republier, puis remettre ces six choix ·

```bash
php artisan vendor:publish --tag=analytics-config --force
```

## L'heure du site

Le site est réglé sur l'heure de Paris, dans `config/app.php`
(`'timezone' => 'Europe/Paris'`). Analytics compte ses jours et affiche ses
heures dans ce fuseau · s'il change, ses jours se redécoupent d'eux-mêmes, en
arrière-plan. Booking a encore son propre fuseau, dans ses réglages (Identité),
sur Paris lui aussi.

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
orphelins restent derrière. `view:cache` compile tout en un seul processus ·
sur le serveur, la course n'a plus lieu.

Sous Windows, elle reste possible, rarement · Laravel n'y écrit pas toujours
l'adresse d'une vue de la même façon quand `view:cache` la compile et quand une
page l'affiche, et la page recompile alors quand même. Si elle tombe, refaire
les deux commandes ci-dessus.
