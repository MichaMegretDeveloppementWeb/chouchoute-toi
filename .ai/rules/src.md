---
paths:
  - 'packages/falcon-booking/src/**'
---

# Src

## Actions et Services se segmentent par espace : Admin, Web, Shared
`Actions/` et `Services/` portent l'axe Espace en plus du domaine : `Actions/{Admin,Web}/{Domaine}/`, `Services/{Admin,Web,Shared}/{Domaine}/`. Le critère n'est pas le nom mais l'appelant, vérifié un par un. `Shared/` est pour ce que les deux espaces demandent réellement (verrou d'agenda, écriture d'un rendez-vous, calcul des créneaux, battement) — jamais par anticipation.

Ce que cela remplace : un drapeau `byAdministrator` qui voyageait dans le DTO et se faisait retester à trois profondeurs pour décider quelles gardes sauter. Ne pas le réintroduire sous un autre nom. Réserver et déplacer existent en deux exemplaires ; celui du comptoir ne demande rien et ne refuse rien, celui du client garde les trois gardes.

`Repositories/`, `Data/`, `Enums/` et `Exceptions/` restent à plat : transverses par nature, et le corpus interdit d'ajouter l'axe par anticipation.

`AgendaLockService` n'est jamais dupliqué : c'est lui qui rend les deux espaces sûrs l'un vis-à-vis de l'autre, et deux verrous qui ne se voient pas ne sont plus un verrou.

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
