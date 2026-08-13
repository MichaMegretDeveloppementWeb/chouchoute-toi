---
paths:
  - 'packages/falcon-booking/resources/js/**'
---

# Js

## FullCalendar : le plugin interaction conditionne tout le glisser
`editable`, `eventStartEditable`, `eventDurationEditable`, `eventDrop`, `eventResize`, `dateClick` et `select` sont **inertes** tant que `fullcalendar/interaction` n'est pas dans le tableau `plugins`. Aucune erreur n'est levée, aucun avertissement en console : les options sont simplement ignorées et rien ne se déplace.

Le piège est que la configuration a l'air complète et que le calendrier fonctionne parfaitement par ailleurs. Le symptôme est un glisser qui « ne prend pas », souvent mis sur le compte de l'outillage de test ou du navigateur.

Repère fiable : le poids du bundle. Sans le plugin, booking-admin.js pèse ~309 ko ; avec, ~345 ko.

Aucun test PHP ne peut couvrir ça. Après toute modification du tableau `plugins`, vérifier le glisser à la main dans le navigateur.
