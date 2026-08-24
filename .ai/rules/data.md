---
paths:
  - packages/falcon-booking/src/Data/Appointment/RecurrenceData.php
---

# Data

## L'horizon de deux ans ne vit jamais dans le constructeur de la règle
Une répétition ne peut pas dépasser deux ans à partir de sa première occurrence. La vérification se fait à l'écran (`HoldsTheEntryForm::applyRepetition`) et dans l'Action qui écrit la série — **jamais** dans le constructeur de `RecurrenceData`.

Deux raisons, toutes deux vérifiées : une même règle tient ou dépasse selon le jour où elle commence, donc l'horizon n'est pas une propriété de la règle seule ; et une règle plus longue existe peut-être déjà en base — un constructeur qui la refuserait ne casserait pas une série mais l'agenda entier, qui lit cette règle pour chaque occurrence affichée.

`MAXIMUM_REPETITIONS` (732) est la borne structurelle absolue, pas l'horizon. La relever ne peut que rendre lisibles davantage de règles stockées ; l'abaisser en rendrait d'anciennes illisibles.

`lastOccurrenceFrom()` situe le dernier bloc par arithmétique et n'étale que celui-là : ne pas le remplacer par `end(repeatsAfter())`, qui construit toute la série et tourne à chaque rendu du panneau.

## La date de fin se compare comme un jour, jamais comme un instant
`isPastTheEnd()` compare deux `toDateString()`. Ce n'est pas une maladresse : `until` est une date saisie à l'écran, sans heure ni fuseau, alors que les occurrences portent celui de l'établissement.

Confrontés comme des instants, les deux ne se recouvrent pas : « jusqu'au 27 août 2028 » posait une visite le 28 à minuit heure de Paris, soit le 27 à 22 h pour qui a saisi la date. Une visite de plus, donc une série jugée trop longue, et un refus nommant la date même qu'on venait de choisir. Ne pas repasser à une comparaison de `CarbonImmutable`.
