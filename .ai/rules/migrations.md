---
paths:
  - 'packages/falcon-booking/database/migrations/**'
---

# Migrations

## Renommer une table : le nom d'une clé étrangère ne suit pas
MySQL garde le nom d'origine d'une contrainte à travers un `RENAME TABLE`, alors que le constructeur de schéma dérive le nom qu'il supprime de la table qu'on lui désigne. `Schema::rename()` puis `dropForeign(['x'])` cherche donc une clé que personne n'a créée : « Ne peut effacer (DROP) … Vérifiez s'il existe ».

L'ordre à tenir : supprimer la clé **avant** le rename, et recréer la nouvelle clé **après**, pour qu'elle porte le nom que sa table lui donnerait. Un index nommé à la main (`fb_attachments_appointment_idx`) n'a pas ce problème : son nom est littéral des deux côtés.

## Larastan ne lit une table que par un littéral, et son cache ment
L'analyse statique construit le schéma en relisant les migrations. `Schema::rename()` n'est suivi que si ses **deux** arguments sont des chaînes littérales (`SchemaAggregator::renameTableThroughStaticCall` exige `String_`) ; derrière une constante de classe, le rename est ignoré en silence et toutes les colonnes du modèle deviennent des « undefined property ». Écrire les noms de table en toutes lettres dans une migration, jamais dans une constante.

Et après une migration qui renomme une table, le cache de résultat de PHPStan garde l'ancien schéma : il signale des colonnes inexistantes sur du code correct. `vendor/bin/phpstan clear-result-cache` avant de conclure à une vraie erreur.

## Un instant se déclare en dateTime(), jamais en timestamp()
MySQL convertit une colonne `timestamp` à l'écriture **et** à la lecture, selon le fuseau de la session — celui du serveur tant qu'aucune clé `timezone` n'est posée sur la connexion. Le paquet écrit de l'UTC : la valeur était donc rangée une ou deux heures à côté, invisiblement, la conversion inverse tournant à la lecture.

Ce qui se voyait quand même : l'heure que la pendule locale saute était **refusée par la base** ; les valeurs cessaient de signifier quoi que ce soit dès que le serveur changeait de fuseau ou qu'un autre outil lisait la table ; et `timestamp` s'arrête en 2038, qu'une répétition de deux ans posée en 2036 dépasse.

Donc : `$table->dateTime('starts_at')`, et **jamais `$table->timestamps()`** — écrire les deux colonnes à la main en `dateTime()->nullable()`. C'est le piège principal : `timestamps()` est la façon ordinaire d'écrire ça et remet l'ancien type sans un mot.

`InvariantsTest::test_no_instant_is_stored_in_a_column_the_server_clock_converts` interroge INFORMATION_SCHEMA et échoue sur toute colonne `timestamp` du paquet.

La conversion elle-même ne déplace aucune donnée : `ALTER … MODIFY … DATETIME` fait relire la valeur à travers le fuseau de session une dernière fois et range ce qu'elle a lu, soit exactement la chaîne écrite. Migration de référence : `2026_08_22_000001_an_instant_stops_depending_on_the_server_clock`.
