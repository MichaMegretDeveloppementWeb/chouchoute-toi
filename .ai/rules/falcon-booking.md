---
paths:
  - 'packages/falcon-booking/**'
---

# Falcon Booking

## `pint --dirty` depuis la racine ne voit rien du paquet
`packages/falcon-booking` est un **dépôt git distinct**. `--dirty` interroge le git de l'hôte, qui n'y voit jamais aucun changement : Pint répond « passed » sans avoir lu un seul fichier du paquet. La consigne de `CLAUDE.md` s'exécute donc sans rien vérifier, et c'est ainsi que quatorze fichiers ont dérivé sans que personne le voie.

Pour tout fichier du paquet : lancer `vendor/bin/pint` **depuis `packages/falcon-booking`** (le binaire reste celui de l'hôte, `../../vendor/bin/pint`), ou lui passer les chemins explicitement. Le préréglage est épinglé dans `packages/falcon-booking/pint.json`.

Même piège pour les tests : la suite du paquet ne se lance pas par `artisan test` de l'hôte, mais par `vendor/bin/phpunit` depuis le paquet.

## L'analyse statique tient le niveau 7, sans baseline
`composer analyse` depuis le paquet, et depuis la racine pour l'hôte. Les deux sont à **PHPStan niveau 7, zéro erreur, sans baseline ni exclusion de règle**. Un niveau inscrit dans `phpstan.neon` est un niveau réellement atteint : si l'analyse échoue, c'est une régression, pas une dette connue.

Sept et pas cinq : c'est le palier qui vérifie les types passés aux fonctions, donc le seul qui voit `max()` recevoir une liste possiblement vide. Le bug qui a motivé cet outillage n'est signalé qu'à partir de là.

Trois réglages sans lesquels l'analyse ment, tous dans `phpstan.neon` :
- `parseModelCastsMethod: true` — les modèles déclarent leurs casts par la méthode `casts()` de Laravel 11, que larastan ne lit pas autrement, et `starts_at` reste alors la chaîne de la colonne au lieu du `CarbonImmutable` que le cast rend ;
- `configDirectories: [config]` — sans quoi chaque `env()` du `config/booking.php` du paquet passe pour un appel fait hors config ;
- `phpstan-bootstrap.php` — enregistre le namespace de vues `booking::` comme le fait le fournisseur, faute de quoi les quinze `view('booking::…')` passent pour des vues absentes.

Les propriétés calculées de Livewire sont déclarées en `@property-read` sur le trait qui porte la méthode. Une nouvelle `#[Computed]` en demande une, sinon l'analyse la voit comme un accès à une propriété inexistante.

Le `phpstan.neon` de l'hôte exclut `config/` (fichiers livrés par Laravel) et `database/seeders/` (Laravel documente `Seeder::$command` comme non nul, ce qui est faux hors artisan ; son propre code se protège par `isset()`, que l'analyseur refuse sur la foi de la même annotation). Les deux absences sont commentées sur place.

## Injection : les Actions par la signature, les dépôts par `boot()`
Les composants Livewire du paquet ne résolvent plus rien par `app()`. La règle tient en une phrase : **une Action s'injecte dans la signature de la méthode qui la déclenche ; un dépôt ou un service de lecture s'injecte une fois par `boot()`**. La raison est la nature des deux : une Action ouvre une transaction et n'est déclenchée que par un geste, donc elle appartient à la signature de ce geste ; un dépôt est lu par des propriétés `#[Computed]` et des méthodes privées, qui n'ont aucune signature à recevoir.

Ce qui rend la première moitié possible : Livewire appelle les actions **et les hooks de cycle de vie** par `wrap($component)->__call()` → `ImplicitlyBoundMethod::call()`. `updatedPieceJointe(AttachFileToAppointmentAction $attach)` fonctionne donc comme `moveAppointment(int $id, string $debut, RescheduleAppointmentAction $action)`. Un paramètre typé n'y consomme aucun argument venu du navigateur, donc il peut se placer avant un paramètre optionnel — et il le doit, PHP dépréciant un paramètre requis déclaré après un optionnel.

Deux conséquences à connaître :
- un appel **direct sur l'instance** (`->instance()->cancel($id)`) court-circuite le conteneur : il faut passer l'Action à la main, ce que fait `CapabilitiesAndActorTest` ;
- `createEntry()` est le seul point que le navigateur appelle sur l'agenda, donc le seul endroit où le conteneur peut remettre les Actions d'écriture. Il les transmet à `saveAppointment()` ou `saveUnavailability()`.

## Deux Actions ne sont pas `final`, et c'est écrit dessus
`SaveScheduleExceptionAction` et `DeleteScheduleExceptionAction` ont perdu leur `final`. Aucun refus qu'elles portent n'est atteignable depuis l'écran, qui résout la ligne sur son propre agenda avant d'appeler : le seul moyen d'éprouver le `try/catch` du composant est une doublure liée par le conteneur, et un paramètre typé n'accepte qu'un sous-type. Même motif que `AppointmentDeletionPolicy`. Les doublures vivent dans `AgendaScreenTest` et **héritent** désormais, ce qui les oblige à suivre la signature de ce qu'elles remplacent.

## `assertReturned(false)` passe sur une erreur 500
Piège du harnais Livewire : quand la requête d'update échoue, `SubsequentRender` rend un `ComponentState` aux effets vides plutôt que de lever. `returns.0` vaut alors `null`, et `assertReturned(false)` compare par `assertEquals`, donc passe. Le test échoue plus loin, sur un `assertDispatched` — ce qui fait lire une erreur fatale comme un toast manquant. Devant ce symptôme, vérifier le code de réponse avant de chercher la logique.

## Le JS du paquet est un point d'entrée et des modules
`resources/js/booking-admin.js` n'est plus qu'une entrée : imports, `alpine:init`, `livewire:init`. Tout le reste vit dans `resources/js/admin/`, un module par composant Alpine, et le calendrier dans son sous-dossier (`index`, `options`, `cards`, `grid`, `navigation`).

Les quatre modules du calendrier **exportent un objet de méthodes**, fusionné dans le composant par étalement. `this` y est le composant, exactement comme si les méthodes étaient écrites dans le littéral. Deux conséquences :
- **`{...objet}` évalue les accesseurs.** `libelleDuMois` et `casesDuMois` restent donc dans le littéral du composant ; y ajouter un `get` dans un module fusionné le copierait comme valeur, une fois pour toutes.
- le littéral d'options de FullCalendar est devenu le corps d'une méthode, `calendarOptions(fuseau, horaires)`, ce qui a évité de réécrire ses 502 lignes.

`BookingAssetsTest` parcourt **tout** `resources/js/`, et non le seul point d'entrée : un import npm ajouté dans un module doit être déclaré par `BookingAssets::npmDependencies()`, sans quoi le build de l'hôte casse.

Le poids du paquet compilé est le repère : **360 010 octets** aujourd'hui. Une baisse sensible après une découpe signale un module oublié à l'import.

## Le gabarit de la modale est une coquille et huit partiels
`appointment-create-modal.blade.php` garde le commentaire d'en-tête, les deux blocs `@php` et la racine Alpine, et n'ordonne que des inclusions.

**`@include` transmet `get_defined_vars()` de l'appelant** : les quarante-cinq variables de mise en page définies dans l'en-tête sont visibles dans chaque partiel sans qu'on ait rien à passer. Ne pas croire l'inverse, un commentaire du paquet l'a longtemps affirmé alors que le fichier qui le portait en dépendait.

Piège rencontré en découpant : `@php(...)` sur une ligne **n'est pas compilé** dans ce fichier, il sort tel quel et ouvre PHP jusqu'à la fin. Employer la forme `@php … @endphp`, y compris sur une ligne, comme le reste du fichier.

Attention aux noms de variables de boucle : `$rangee` est une chaîne de classes de l'en-tête, et la masquer dans un `@foreach` casse la mise en page sans lever d'erreur.

## Les fins de ligne viennent de `.gitattributes`, pas de l'outil
`core.autocrlf` vaut `true` sur cette machine, donc git rend du CRLF au checkout sauf indication contraire. L'index est propre (100 % LF) ; c'est la copie de travail qui se salit, et Pint signale alors `line_ending` sur des fichiers qui n'ont rien d'autre à se reprocher.

`packages/falcon-booking/.gitattributes` porte `* text=auto eol=lf`, comme celui de l'hôte. Ne pas le retirer : sans lui la dérive revient à chaque `git checkout`.

## Commentaires du paquet : régime 2 (doctrine pro), déjà appliqué
Le chantier de nettoyage `commentaires.md` a été fait sur tout falcon-booking. L'état livré est le régime 2, pas le régime 1 : ne pas y revenir en ajoutant du contexte de session.

Ce qui est interdit dans ce paquet :
- toute mention d'un client ou d'un cas d'usage particulier (c'est un paquet généraliste, pas un développement sur mesure) ;
- la narration : historique d'un bug corrigé, « avant, l'écran faisait… », dates de décision, références de lot ou de sprint, renvois au cahier des charges ;
- les tournures genrées désignant l'utilisatrice (« she », « her »), et les données de jeu d'essai reprenant le nom d'un vrai établissement ;
- le tiret cadratin U+2014 (utiliser « : », « , » ou reformuler).

Ce qui est attendu : PHPDoc concis en anglais décrivant ce que fait la méthode, plus le « pourquoi » non évident quand il existe. Rien sur le trivial, rien en décoration de vue. Seuls les TODO restent tolérés, en anglais et sans référence interne.

Les strings runtime (messages de validation, toasts, exceptions, textes UI) restent en français et ne se touchent pas : des tests les assertent.
