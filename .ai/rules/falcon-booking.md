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
