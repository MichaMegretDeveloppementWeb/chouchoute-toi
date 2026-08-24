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
`SaveScheduleExceptionAction` et `DeleteScheduleExceptionAction` ont perdu leur `final`. Aucun refus qu'elles portent n'est atteignable depuis l'écran, qui résout la ligne sur son propre agenda avant d'appeler : le seul moyen d'éprouver le `try/catch` du composant est une doublure liée par le conteneur, et un paramètre typé n'accepte qu'un sous-type. Même motif que `AppointmentDeletionPolicy`. Les doublures vivent dans `AgendaUnavailabilityTest` et **héritent** désormais, ce qui les oblige à suivre la signature de ce qu'elles remplacent.

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

## Un commentaire de ce paquet est une aide à la navigation, rien d'autre
La définition tient dans l'usage qu'on en fait : je cherche quelle partie du code fait une chose précise, je vais à peu près au bon endroit, je regarde les méthodes, je m'aide de leur nom, **puis je lis le commentaire pour savoir si c'est la bonne** — sans avoir à lire le corps de chacune.

Il en découle une règle et une seule : **court, et il dit ce que fait la méthode.** Pas de nombre de phrases imposé, contraindre un compte créant ses propres problèmes. Un bloc court avant chaque classe et chaque méthode ; dans le corps, seulement là où le code est vraiment difficile, et jamais systématiquement.

Ce qui n'a pas sa place dans un commentaire :
- **les décisions** : « écrit ainsi plutôt qu'autrement parce que… » ;
- **l'historique** : « avant, l'écran faisait X ; ça ne marchait pas ; donc Y », dates, références de lot, renvois au cahier des charges ;
- **les mesures et les anecdotes** : « mesuré à 300 ms », « cinq cent une instructions », « trouvé à l'écran » ;
- **le détail exhaustif** du fonctionnement, que le corps dit déjà ;
- toute mention d'un client, d'un produit concurrent ou d'un cas d'usage particulier : c'est un paquet généraliste ;
- les tournures genrées désignant l'utilisatrice (« she », « her »), et les jeux d'essai reprenant le nom d'un vrai établissement ;
- le tiret cadratin U+2014 (utiliser « : », « , » ou reformuler) ;
- les `TODO`, `FIXME`, `XXX` : il n'en reste aucun.

Ce qui reste : le bloc court, en **anglais**, et les `@param`, `@return`, `@throws` que PHPStan niveau 7 exige.

**Où va ce qui est retiré.** Un savoir ne migre vers `.ai/rules` **que si son oubli recréerait un vrai défaut**. Tout relocaliser reviendrait à déplacer l'encombrement, pas à le retirer. Le reste s'en va, et c'est assumé. Six savoirs ont migré ainsi : la comparaison de la date de fin, le middleware persistant, le cadre de l'acompte, l'ordre croissant des verrous, le `withCount` de la projection, et le critère de trace retiré.

**Un gabarit et une feuille de style ne narrent pas leur rendu.** Le balisage se lit seul. Ce qui subsiste est ce que la déclaration ne peut pas dire : un élément vide qui réserve une place, un `wire:key` sans lequel une modale se referme, un `wire:ignore` que le morph viderait, une mesure sous le pixel, un couplage avec le JS.

Les strings runtime (messages de validation, toasts, exceptions, textes UI) restent en français et ne se touchent pas : des tests les assertent. C'est la seule raison pour laquelle il subsiste un tiret cadratin dans le paquet, dans l'alerte de fuseau de `business.blade.php`.

### État livré, en lignes de commentaire

| | avant | après |
|---|---|---|
| `src/` | 5 556 (29 %) | **3 729** (22 %) |
| `tests/` | 3 097 (16 %) | **2 993** (15 %) |
| `resources/js/` | 885 (37 %) | **701** (31 %) |
| `resources/css/` | 202 (28 %) | **186** (27 %) |
| `resources/views/` | 246 (7 %) | **86** (2 %) |

### L'invariant à reproduire si le chantier est rejoué
Comparer avant et après les fichiers **privés de leurs commentaires** : c'est plus fort que les tests, cela prouve que *seuls* des commentaires ont bougé.

- **PHP** : `token_get_all` sans `T_COMMENT`, `T_DOC_COMMENT` ni `T_WHITESPACE`, sur `src`, `tests`, `database`, `config`, `routes`. 251 fichiers.
- **Blade** : la même idée sur le **source** et non sur le compilé, Livewire dérivant une graine de clé du hachage du source. Retirer `{{-- --}}`, les commentaires PHP des blocs `@php`, puis normaliser les blancs. 48 gabarits.
- **JS et CSS** : le paquet compilé doit garder **le même nom de fichier**, qui porte le hachage de son contenu, et la même taille à l'octet.

**Sonder l'outil avant de le croire.** Un `grep` qui ne trouve rien peut ne rien chercher : `$'—'` ne matche rien dans Git Bash, et `grep -P` y refuse l'UTF-8. Éprouver le motif sur un cas connu-présent avant de conclure à zéro.

### Trois pièges payés en le faisant
- Blade compile les directives avant de retirer les commentaires, donc **aucun commentaire ne porte d'arobase** (contrôle : compiler chaque gabarit puis `php -l`).
- **Tailwind lit aussi les commentaires** : retirer un nom de classe cité entre accents graves retire une règle du paquet compilé. Relever les jetons entre accents graves avant et après, et les comparer.
- **Les docblocs empilés ne lèvent rien.** Trois en ont été trouvés — `SettingsRepository::setMany`, `ServiceWriteService::delete`, `HoldsTheEntryForm::applyRepetition` — où deux blocs se suivaient devant une seule méthode. PHP garde le dernier ; le premier était selon les cas un `@param` orphelin, une description périmée, ou la description de la méthode *suivante*, qui se retrouvait donc sans la sienne. Rien ne les signale : ni PHPStan, ni Pint, ni un test.

## Un fichier au-dessus de 400 lignes est nommé ici, ou il se découpe
Le socle (`structure-fichiers.md`, principe directeur) pose **~400 lignes en règle générale, 1000 en exception justifiée**, et ne connaît pas de frontière de langage : un `.js`, un `.css` ou un `.blade.php` compte comme une classe PHP.

**Aucun fichier du paquet n'est au-dessus de 1000.** Onze restent au-dessus de 400, et les voici avec leur raison. La liste est le contrat : y ajouter un fichier, ou le découper, mais ne pas le laisser dériver en silence.

| fichier | lignes | pourquoi il reste entier |
|---|---|---|
| `resources/js/admin/calendar/options.js` | 527 | Un seul objet de configuration remis à une seule bibliothèque. Le découper disperse un contrat unique et impose du `.call(this)`. Le paquet n'exécute aucun JS dans ses tests : la seule preuve mécanique disponible est le hachage du paquet compilé, que justement un remaniement détruit. |
| `src/Data/Appointment/RecurrenceData.php` | 500 | L'arithmétique de calendrier est **un seul algorithme** : quatre générateurs et le raccourci qui doit tomber sur la même date qu'eux. La mise en mots en est déjà sortie (`PutsARecurrenceInWords`). |
| `src/Livewire/Admin/ScheduleForm.php` | 445 | Un écran, un formulaire. Les cinq aides privées ne sont lues que par `save()`. |
| `src/Livewire/Admin/ScheduleExceptions.php` | 441 | Idem. La seule couture est `rules()`/`messages()`, quarante-quatre lignes qu'on irait alors chercher dans un autre fichier pour savoir ce qu'un champ refuse. |
| `tests/Feature/Appointment/SeriesScopeFromTheAgendaTest.php` | 460 | Un écran chacun, ou un invariant chacun. Leurs sujets sont déjà nommés par des marqueurs `// ──`, et à un dixième du seuil la couture coûterait plus qu'elle ne rend. |
| `tests/Feature/Appointment/AppointmentFormComputedValuesTest.php` | 459 | ″ |
| `tests/Feature/Catalogue/CatalogueScreenTest.php` | 448 | ″ |
| `tests/Feature/Package/FailuresAreToldTest.php` | 447 | ″ |
| `tests/Feature/Appointment/AppointmentLinesTest.php` | 432 | ″ |
| `tests/Feature/Schedule/ScheduleScreenTest.php` | 428 | ″ |
| `tests/Feature/Appointment/RepeatAnAppointmentTest.php` | 404 | ″ |

**On découpe sur une couture, jamais sur un compteur.** Un fichier cohésif coupé pour satisfaire un nombre se lit moins bien, pas mieux.

### L'invariant à reproduire si le chantier est rejoué
Un chantier qui déplace du code ne peut pas s'appuyer sur le flux de jetons d'un fichier : c'est justement ce qui bouge. Deux mesures, en lecture seule, avant et après **chaque lot** :

- **L'empreinte par méthode.** Pour chaque fonction de `src`, `tests`, `database`, `config`, `routes` : `nom` + md5 de son corps en jetons, privé des commentaires et des blancs, l'ensemble trié. **La classe déclarante est délibérément absente** : une découpe déplace une méthode d'un trait à l'autre, la classe change, le couple (nom, corps) non. C'est ce qui attrape les trois vraies façons de se tromper — un corps modifié « au passage », une méthode perdue, une méthode dupliquée.
- **La surface publique des composants Livewire.** Par réflexion : toute propriété publique et toute signature de méthode publique. C'est le contrat du navigateur (`wire:model`, `$wire.methode()`), et rien d'autre ne le tient.

### Ce que ces deux mesures ne voient pas
Elles sont aveugles à ce qui n'est ni un corps ni une surface publique, et les deux cas se sont présentés :

- **La visibilité.** `CountsStatements::statementsOf()` est déclarée `private`. Posé sur une base abstraite, le trait devient invisible aux classes filles : `Call to private method … from scope …`, à l'exécution seulement. Un trait se compose **sur la classe qui l'appelle**, pas sur la base, sauf à élargir sa visibilité.
- **Le `use` manquant.** `use MonTrait;` dans un corps de classe se résout dans le namespace courant : sans l'import, l'erreur est fatale et aucun test PHP ne la voit autrement qu'en tombant.

Et quatre pièges relevés avant de commencer, tous vérifiés depuis : `reset()` nomme ses propriétés **par chaînes** (`newAppointment` en liste trente-six, désormais réparties sur deux traits — rien ne vérifie que la liste reste complète) ; un `#[Computed]` emporte son `@property-read` sur le trait qui le porte ; une propriété ne peut être déclarée que par **un seul** trait, la répartition est une partition ; les attributs Livewire (`#[Url]`, `#[On]`) voyagent avec ce qu'ils décorent.

**Le CSS a son propre invariant** : `@import` est inliné par Vite dans l'ordre des imports, et cet ordre **est** la cascade. Le hachage du paquet compilé est donc la preuve que le rendu n'a pas bougé, pas une formalité.

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
