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
