---
paths:
  - 'packages/falcon-booking/src/**'
---

# Src

## La taille des fichiers est tenue, et la liste des exceptions est dans `falcon-booking.md`
Trois fichiers de `src/` restent au-dessus de ~400 lignes, chacun avec sa raison écrite : `RecurrenceData` (500), `ScheduleForm` (445), `ScheduleExceptions` (441). La règle complète — le seuil, les onze exceptions du paquet, l'invariant à reproduire pour déplacer du code sans le casser — est sous « Un fichier au-dessus de 400 lignes est nommé ici, ou il se découpe » dans `.ai/rules/falcon-booking.md`, qui couvre tout le paquet et pas seulement cette couche.

## Actions et Services se segmentent par espace : Admin, Web, Shared
`Actions/` et `Services/` portent l'axe Espace en plus du domaine : `Actions/{Admin,Web}/{Domaine}/`, `Services/{Admin,Web,Shared}/{Domaine}/`. Le critère n'est pas le nom mais l'appelant, vérifié un par un. `Shared/` est pour ce que les deux espaces demandent réellement (verrou d'agenda, écriture d'un rendez-vous, calcul des créneaux, battement) — jamais par anticipation.

Ce que cela remplace : un drapeau `byAdministrator` qui voyageait dans le DTO et se faisait retester à trois profondeurs pour décider quelles gardes sauter. Ne pas le réintroduire sous un autre nom. Réserver et déplacer existent en deux exemplaires ; celui du comptoir ne demande rien et ne refuse rien, celui du client garde les trois gardes.

## L'axe Domaine est toujours requis ; c'est l'axe Espace qui est optionnel
Ne pas confondre les deux, l'erreur a déjà été faite ici. `structure-fichiers.md` §19 dit « **Axe Domaine (toujours)** » et son tableau donne `Data/{Domaine}/`, `Enums/{Domaine}/`, `Exceptions/{Domaine}/`, `Repositories/{Domaine}/`. La phrase « ne pas ajouter l'axe par anticipation pure » du §24 vise l'axe **Espace**, et lui seul.

Un chantier antérieur a cité cette phrase pour laisser `Data/` à plat. C'était une mauvaise lecture, et elle avait été gravée ici même. Les six domaines du paquet — **Appointment, Catalogue, Client, Schedule, Settings, Agenda** — viennent de `Actions/` et `Services/`, déjà segmentés : un fichier se cherche au même endroit dans toutes les couches.

Trois exceptions, chacune pour une raison :

- **`Models/`** reste à plat, le corpus le range explicitement dans le plat.
- **`Exceptions/BaseBookingException`** reste à la racine de sa couche : elle n'appartient à aucun domaine, elle les porte tous.
- **`tests/Isolation/`** reste à plat : son axe n'est pas le domaine mais la contrainte d'exécution — ce qui ne peut pas tourner dans une transaction. Huit fichiers cohérents ne sont pas un fourre-tout.

Les tests qui vérifient le paquet lui-même — installation, diagnostic, assets, invariants de schéma, contrat d'erreur — vont dans `Package/`, que le corpus autorise comme **famille fonctionnelle** au même titre qu'une entité métier.

**Un renommage de ce genre se fait à la main, fichier par fichier.** Une tentative par script a vidé 297 fichiers : `preg_replace` rend `null` quand sa regex ne compile pas, et ce `null` avait été réécrit tel quel. Trois contrôles avant chaque commit : aucun fichier vide, aucune occurrence de l'ancien chemin qualifié, aucune ligne ajoutée hors `use` et `namespace`.

**Le piège du déplacement des tests** : tout chemin en `__DIR__` ou `dirname(__DIR__, n)` descend d'un niveau avec le fichier. Sept sites dans quatre fichiers, que seule l'exécution révèle.

`AgendaLockService` n'est jamais dupliqué : c'est lui qui rend les deux espaces sûrs l'un vis-à-vis de l'autre, et deux verrous qui ne se voient pas ne sont plus un verrou.

**Les agendas se verrouillent par clé croissante, toujours.** Deux écrivains qui touchent les deux mêmes agendas prennent le plus petit d'abord, donc aucun ne tient l'un en attendant l'autre : aucun cycle ne peut se former. La ligne du rendez-vous se verrouille après eux, et c'est le seul ordre que suit tout le paquet. Une instruction par agenda plutôt qu'une requête ordonnée pour tous : l'ordre vient alors du code et non du plan que choisit l'optimiseur.

`assertFree()` ne concerne que l'espace client, deux personnes ne pouvant pas se voir vendre la même heure. L'établissement n'a rien à y appeler : superposer deux cartes est une façon normale de tenir un agenda, et elles sont visiblement empilées à l'écran de qui les a empilées.

## Une insertion groupée ne déclenche ni cast ni hook : passer par un modèle jamais enregistré
`insert()` court-circuite tout ce qu'Eloquent fait sur le chemin d'une ligne. Trois conséquences, et chacune a coûté cher ailleurs :

- **Les casts.** `UtcInstant` convertit en UTC dans `set()`. Un Carbon passé directement à `insert()` écrit son heure murale : la ligne tombe une ou deux heures trop tôt, et personne ne le voit puisque la lecture la relit comme de l'UTC. Construire les lignes par `(new Model)->forceFill([...])->getAttributes()` : traverser `setAttribute()` est ce qui applique les casts.
- **Les hooks `saving()`.** `occupied_until` est dérivé par un hook sur `Appointment` et sur `AppointmentLine`. C'est la colonne que lisent `OccupationRepository` et `AgendaBusynessRepository` : sans elle, la ligne est posée sur l'agenda et invisible à la détection de chevauchement. La poser à la main, avec la même formule.
- **Les horodatages.** `created_at`/`updated_at` ne sont pas remplis. `AppointmentLine` n'en porte pas (`$timestamps = false`), le rendez-vous si.

Les identifiants ne reviennent pas non plus : les relire, et vérifier le compte contre ce qui a été demandé avant tout appariement par position.

Le filet : comparer colonne par colonne une ligne écrite en masse et une écrite par la voie ordinaire (`SeriesWrittenInOneGoTest`).

## Le fuseau de l'établissement se lit par BusinessClock, jamais par config()
`business.timezone` est un **réglage**, pas une clé de configuration : un institut qui déménage n'a pas à redéployer. `config('booking.timezone')` n'en est que la valeur d'amorce, et ne doit plus être lu nulle part dans `src/`.

`Services/Shared/Time/BusinessClock` est le seul endroit qui sait quelle zone c'est : `zone()`, `now()`, `today()`, `on($instant)` — le même instant à l'heure de l'institut — et `at('2026-09-07 09:00')` — une heure tapée, devenue l'instant qu'elle nomme.

`at()` compte : sans zone explicite, `CarbonImmutable::parse()` lit dans `app.timezone`, qui vaut `UTC` sur une installation Laravel par défaut. Toute heure tapée s'y décalerait d'une à deux heures.

Les horaires d'ouverture ne passent pas par là : ce sont des heures de paroi (`WallClockTime`), pas des instants. Neuf heures reste neuf heures des deux côtés d'un changement d'heure.

Changer ce réglage ne déplace aucun instant stocké — ils sont absolus — mais déplace l'heure d'affichage de tous. L'écran Réglages porte l'avertissement, et un test l'assert sur son libellé.
