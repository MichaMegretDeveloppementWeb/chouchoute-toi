---
paths:
  - 'packages/falcon-booking/database/migrations/**'
---

# Migrations

## Tant que le paquet n'est pas en production, une migration se corrige, elle ne se rattrape pas
Le paquet arrive toujours sur une base neuve. Une table qui n'a pas la bonne structure ne se répare donc **pas** par une migration de plus : on retourne modifier celle qui la crée, on supprime le correctif, et on relance `migrate:fresh` — avec des seeders remis à jour si la structure a bougé sous eux.

L'empilement `créer / puis renommer / puis changer la clé` n'a de sens qu'après une mise en production, quand des données existantes interdisent de repartir de zéro. Avant, il ne fait qu'accumuler de la cérémonie : `service_id` sur les rendez-vous a été rendue nullable, sa clé refaite, puis la colonne supprimée trois fichiers plus loin — pour une colonne qui, dans la bonne structure, n'existe pas.

Corollaire : **une migration ne remplit jamais de données.** Pas de `DB::table()->update`, pas de boucle de reprise, pas de `down()` qui supprime des lignes. Les valeurs de départ se posent en `default()` ou dans un seeder.

Douze fichiers, une table chacun. Y ajouter un treizième qui en modifie une autre, c'est repartir dans ce qui vient d'être défait.

**Comment le vérifier** : comparer le schéma avant et après par `INFORMATION_SCHEMA` — colonnes, types, nullabilité, défauts, clés, index, contraintes. Le diff doit être vide. Un test qui relit les migrations ne prouverait que leur accord avec elles-mêmes.

**Le piège du pliage** : `$table->timestamps()` repose des colonnes `timestamp`. Voir la règle sur `dateTime()` ci-dessous.

## Renommer une table : le nom d'une clé étrangère ne suit pas
MySQL garde le nom d'origine d'une contrainte à travers un `RENAME TABLE`, alors que le constructeur de schéma dérive le nom qu'il supprime de la table qu'on lui désigne. `Schema::rename()` puis `dropForeign(['x'])` cherche donc une clé que personne n'a créée : « Ne peut effacer (DROP) … Vérifiez s'il existe ».

L'ordre à tenir : supprimer la clé **avant** le rename, et recréer la nouvelle clé **après**, pour qu'elle porte le nom que sa table lui donnerait. Un index nommé à la main n'a pas ce problème : son nom est littéral des deux côtés.

Le paquet ne renomme plus aucune table — la règle précédente a supprimé le seul cas. Elle reste écrite ici parce qu'elle se paie cher le jour où le paquet sera en production et où un rename deviendra la seule voie.

## Larastan ne lit une table que par un littéral, et son cache ment
L'analyse statique construit le schéma en relisant les migrations. `Schema::rename()` n'est suivi que si ses **deux** arguments sont des chaînes littérales (`SchemaAggregator::renameTableThroughStaticCall` exige `String_`) ; derrière une constante de classe, le rename est ignoré en silence et toutes les colonnes du modèle deviennent des « undefined property ». Écrire les noms de table en toutes lettres dans une migration, jamais dans une constante.

Et après une migration qui renomme une table, le cache de résultat de PHPStan garde l'ancien schéma : il signale des colonnes inexistantes sur du code correct. `vendor/bin/phpstan clear-result-cache` avant de conclure à une vraie erreur.

## Un instant se déclare en dateTime(), jamais en timestamp()
MySQL convertit une colonne `timestamp` à l'écriture **et** à la lecture, selon le fuseau de la session — celui du serveur tant qu'aucune clé `timezone` n'est posée sur la connexion. Le paquet écrit de l'UTC : la valeur était donc rangée une ou deux heures à côté, invisiblement, la conversion inverse tournant à la lecture.

Ce qui se voyait quand même : l'heure que la pendule locale saute était **refusée par la base** ; les valeurs cessaient de signifier quoi que ce soit dès que le serveur changeait de fuseau ou qu'un autre outil lisait la table ; et `timestamp` s'arrête en 2038, qu'une répétition de deux ans posée en 2036 dépasse.

Donc : `$table->dateTime('starts_at')`, et **jamais `$table->timestamps()`** — écrire les deux colonnes à la main en `dateTime()->nullable()`. C'est le piège principal : `timestamps()` est la façon ordinaire d'écrire ça et remet l'ancien type sans un mot.

`InvariantsTest::test_no_instant_is_stored_in_a_column_the_server_clock_converts` interroge INFORMATION_SCHEMA et échoue sur toute colonne `timestamp` du paquet. C'est le filet : il n'y a pas à se souvenir de la règle, il y a à ne pas la contourner.

Les douze migrations déclarent donc leurs instants en `dateTime()` dès la création, et écrivent `created_at` / `updated_at` à la main.
