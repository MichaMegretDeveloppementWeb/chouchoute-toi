---
paths:
  - 'packages/falcon-booking/**'
---

# Falcon Booking

## Chaque règle de validation a son message, et un test l'exige
`tests/Feature/EveryRuleHasAMessageTest.php` monte chaque formulaire de l'admin, développe son `rules()` en `champ.règle` et exige une entrée dans `messages()`. Il couvre les branches d'état (`entryType` rendez-vous/indisponibilité, `formAllDay`), sans quoi la moitié des règles ne serait jamais lue.

Ce n'est pas une exigence de style. Le projet n'a **aucune** traduction de framework pour ses propres règles : `lang/fr/validation.php` est un filet pour ce que le moteur produit hors de nos composants (l'upload Livewire, validé dans une requête séparée que `messages()` n'atteint jamais), pas la source des messages. Une règle sans message affichait donc `validation.max.string` à qui saisissait un code postal trop long.

Ajouter une règle sans son message fait échouer ce test. C'est voulu.

## `integer` n'est pas décorative : elle décide comment `min` et `max` mesurent
Piège coûteux, vérifié en cassant trois tests. On croit `'integer'` redondante sur une propriété `public int` — Livewire caste à l'hydratation, la règle ne verra jamais autre chose qu'un entier. C'est vrai, et ce n'est pas la question.

Laravel choisit la sémantique de `min`/`max`/`between`/`size` selon la **présence d'une règle numérique dans la liste** (`getSize()` : `is_numeric($value) && $hasNumeric`). Retirer `'integer'` fait donc basculer `min:5` de « la valeur vaut au moins 5 » à « la chaîne fait au moins 5 caractères », et un pas de créneaux de 15 minutes se voit refusé parce que « 15 » n'a que deux caractères.

Règle : **ne jamais retirer `'integer'` d'une liste qui contient `min`, `max`, `between` ou `size`.** `'string'` et `'boolean'` n'ont pas ce rôle et peuvent, eux, être retirés quand le type de la propriété les garantit.

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

Ce qui rend la première moitié possible : Livewire appelle les actions **et les hooks de cycle de vie** par `wrap($component)->__call()` → `ImplicitlyBoundMethod::call()`. `updatedUpload(AttachFileToClientAction $attach)` fonctionne donc comme `moveAppointment(int $id, string $debut, RescheduleAppointmentAction $action)`. Un paramètre typé n'y consomme aucun argument venu du navigateur, donc il peut se placer avant un paramètre optionnel — et il le doit, PHP dépréciant un paramètre requis déclaré après un optionnel.

Deux conséquences à connaître :
- un appel **direct sur l'instance** (`->instance()->cancel($id)`) court-circuite le conteneur : il faut passer l'Action à la main, ce que fait `CapabilitiesAndActorTest` ;
- `createEntry()` est le seul point que le navigateur appelle sur l'agenda, donc le seul endroit où le conteneur peut remettre les Actions d'écriture. Il les transmet à `saveAppointment()` ou `saveUnavailability()`.

## Un paramètre `enum` sur une méthode appelée par le navigateur casse la résolution
Corollaire de la section précédente, et il tire dans l'autre sens. Livewire résout **tout** paramètre typé par une classe via le conteneur, qu'il porte une valeur par défaut ou non. Un enum est une classe et n'est pas instanciable : `saveAppointment(..., ?AppointmentSeriesScope $scope = null)` faisait donc échouer chaque appel, y compris ceux qui ne passent pas de portée.

La forme qui marche : la valeur voyage en `string` sur la méthode publique, qui la convertit par `tryFrom()` — jamais `from()`, qui lève un `ValueError` portant le nom de la classe jusqu'à l'écran. Le corps partagé, lui, est privé et prend l'enum.

## Une projection est une promesse sur ce que la carte peut dire
`AgendaRepository` ne sélectionne qu'une dizaine de colonnes, à raison : la table en porte une trentaine, dont la note interne et l'adresse. Mais une colonne oubliée ne lève rien — l'attribut revient `null`, et le drapeau qui en dépend devient faux pour tout le monde. Le `⟳` des visites récurrentes est resté invisible ainsi, code de rendu écrit et testé au niveau du modèle.

Règle : **ajouter une colonne à la projection en même temps que ce qui la lit**, et l'éprouver depuis `eventsBetween()` et non depuis le modèle. Un test qui lit `$appointment->series_id` passe quoi qu'il arrive.

Le `withCount('lines')` de la même requête relève du même piège, et il est plus discret encore : il ne coûte pas de requête, il voyage dans le `select`. Le retirer ne casse rien, ne ralentit rien et ne lève rien — il fait juste disparaître le « +2 » de toutes les cartes à plusieurs prestations.

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

## Commentaires du paquet : régime 2 (doctrine pro), appliqué partout
Le chantier de nettoyage `commentaires.md` a été passé sur tout falcon-booking, en quatre lots : les assets, les gabarits, le code, les tests. L'état livré est le régime 2, pas le régime 1 : ne pas y revenir en ajoutant du contexte de session.

Ce qui est interdit dans ce paquet :
- toute mention d'un client ou d'un cas d'usage particulier (c'est un paquet généraliste, pas un développement sur mesure) ;
- la narration : historique d'un bug corrigé, « avant, l'écran faisait… », dates de décision, références de lot ou de sprint, renvois au cahier des charges ;
- les tournures genrées désignant l'utilisatrice (« she », « her »), et les données de jeu d'essai reprenant le nom d'un vrai établissement ;
- le tiret cadratin U+2014 (utiliser « : », « , » ou reformuler) ;
- les `TODO`, `FIXME`, `XXX` : il n'en reste aucun. Ce qui était un vrai périmètre de règle a été reformulé en phrase.

Ce qui est attendu : PHPDoc concis en anglais décrivant ce que fait la méthode, plus le « pourquoi » non évident quand il existe. Rien sur le trivial.

**Un gabarit et une feuille de style ne narrent pas leur rendu.** C'est le principe de placement de la brique, valable dans les deux régimes : le balisage se lit seul. Les 35 gabarits sont passés de 573 lignes de commentaire à 91, la feuille de l'agenda de 300 à 99. Ce qui subsiste est ce que la déclaration ne peut pas dire : un élément vide qui réserve une place, un `wire:key` sans lequel une modale se referme, un `wire:ignore` que le morph viderait, une mesure sous le pixel, un couplage avec le JS. Une mesure relevée sur la référence, un choix de graisse, la raison d'une colonne : rien de tout cela n'a sa place dans une vue. Ce qui traverse plusieurs fichiers va dans `.ai/rules/views.md`.

Les strings runtime (messages de validation, toasts, exceptions, textes UI) restent en français et ne se touchent pas : des tests les assertent.

**L'invariant à reproduire** si le chantier est rejoué : comparer avant et après les fichiers privés de leurs commentaires. En PHP, `token_get_all` sans `T_COMMENT` ni `T_DOC_COMMENT`. En Blade, la même chose sur le **source** et non sur le compilé — Livewire dérive une graine de clé du hachage du source, donc retirer un commentaire change le compilé sans rien changer au rendu. En JS et CSS, le paquet compilé doit garder **le même nom de fichier**, qui porte le hachage de son contenu.

Deux pièges vus en le faisant : Blade compile les directives avant de retirer les commentaires, donc aucun commentaire ne porte d'arobase (contrôle : compiler chaque gabarit puis `php -l`) ; et Tailwind lit aussi les commentaires, donc retirer un nom de classe cité entre accents graves retire une règle du paquet compilé.

## Le journal est celui des rendez-vous, et il le reste
Décision prise, pas un oubli : modifier un réglage, poser une fermeture ou archiver une prestation ne laisse **aucune entrée** au journal. Ne pas « corriger » cela au prochain audit.

La raison : une table qui enregistre chaque geste devient énorme et se remplit surtout de ce que personne ne relira — le catalogue, les horaires, les allers-retours de réglage. Le jour où quelqu'un cherche vraiment quelque chose, il le cherche dans du bruit.

La contrepartie est connue et acceptée : changer le fuseau horaire déplace l'heure affichée de tous les rendez-vous sans que rien ne dise qui l'a fait. C'est l'écran des réglages qui porte l'avertissement, au moment du geste.

Ce qui va au journal reste ce qui l'y allait : les transitions d'état, l'écriture d'un rendez-vous, l'effacement d'un rendez-vous et celui d'une série. Le `BookingLog` applicatif, lui, garde les refus et les échecs — c'est une trace d'exploitation, pas un journal d'audit, et elle tourne.

## Le middleware persistant n'accueille jamais ce que la route de Livewire porte déjà
`PersistentMiddlewareResolver` écarte tout ce que le groupe `web` embarque, et cet écart n'est pas une optimisation : la route `/livewire/update` porte déjà ce groupe. Déclarer session, cookies ou CSRF comme persistants les fait tourner une seconde fois, et redémarrer une session déjà démarrée la détruit. L'utilisatrice est déconnectée en pleine action, après une écriture réussie.

Ce qui a sa place dans la configuration est ce que l'hôte a ajouté **par-dessus** `web` — un `auth:admin`, par exemple. Et comme le middleware persistant est global à Livewire, cela vaut pour tous les composants de l'hôte, pas seulement les écrans du paquet.

## « Avoir été déplacé » n'est pas un critère de trace, et ne doit pas le redevenir
`AppointmentDeletionPolicy::leavesATrace()` retient trois cas : un encaissement, une origine autre que `admin`, un rappel parti, un état honoré ou absent. Chacun nomme **quelque chose qui est sorti de l'établissement** — quelqu'un dehors croit quelque chose, et il faudra peut-être dire ce qu'il en est advenu.

Un quatrième a existé : avoir été déplacé, au motif qu'un déplacement suppose qu'on a prévenu. C'est faux. L'établissement qui traîne sa propre carte ne prévient personne, et rien n'enregistre qu'un appel a été passé. C'était le seul des quatre à ne rien nommer de sortant, et il gardait des entrées d'effacement pour des lignes qui n'avaient jamais quitté l'arrière-boutique. Ne pas le rajouter.

Le refus porté par `refusalFor()` n'est pas la barrière de l'établissement, qui peut toujours retirer une ligne fautive : c'est le point d'extension d'un hôte qui donne le droit d'effacer à un acteur plus étroit. L'encaissement y figure parce que la facturation n'est pas construite et qu'un montant vit sur la ligne elle-même ; ce paquet tient un agenda, il ne certifie pas une recette.

## L'acompte n'est pas construit, mais son cadre l'est : ne pas le retirer
`Appointment::holdHasExpired()` n'a aucun appelant, et c'est voulu. Autour d'elle tient tout ce qu'il faut le jour où l'acompte se construit : la colonne `hold_expires_at`, l'index `fb_appointments_hold_idx` qui la couvre, les états `PendingPayment` et `Expired`, et l'Action de transition qui efface déjà le marqueur au passage.

Un audit du code mort trouvera cet ensemble et voudra le supprimer. Ne pas le faire : ce n'est pas un reste, c'est une amorce. Un index posé pour une requête que personne n'a encore écrite dit qui l'écrira.
