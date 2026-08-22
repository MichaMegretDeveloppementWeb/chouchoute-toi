---
paths:
  - packages/falcon-booking/src/Data/RecurrenceData.php
---

# Data

## L'horizon de deux ans ne vit jamais dans le constructeur de la règle
Une répétition ne peut pas dépasser deux ans à partir de sa première occurrence. La vérification se fait à l'écran (`HoldsTheEntryForm::applyRepetition`) et dans l'Action qui écrit la série — **jamais** dans le constructeur de `RecurrenceData`.

Deux raisons, toutes deux vérifiées : une même règle tient ou dépasse selon le jour où elle commence, donc l'horizon n'est pas une propriété de la règle seule ; et une règle plus longue existe peut-être déjà en base — un constructeur qui la refuserait ne casserait pas une série mais l'agenda entier, qui lit cette règle pour chaque occurrence affichée.

`MAXIMUM_REPETITIONS` (732) est la borne structurelle absolue, pas l'horizon. La relever ne peut que rendre lisibles davantage de règles stockées ; l'abaisser en rendrait d'anciennes illisibles.

`lastOccurrenceFrom()` situe le dernier bloc par arithmétique et n'étale que celui-là : ne pas le remplacer par `end(repeatsAfter())`, qui construit toute la série et tourne à chaque rendu du panneau.
