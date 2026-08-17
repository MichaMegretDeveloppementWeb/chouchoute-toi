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

## Un panneau `fixed` dans une modale : origine et racine
Deux pièges rencontrés en sortant le catalogue déroulant du conteneur de défilement de la modale.

**L'origine.** Un `position: fixed` se place contre la fenêtre, sauf si un ancêtre établit un bloc conteneur. Le panneau des modales du kit porte `scale-100` à l'ouverture, et Tailwind 4 l'écrit sur la propriété **`scale`**, pas sur `transform` : une sonde qui ne teste que `transform` ne le voit pas. Le panneau atterrissait décalé de tout le coin supérieur gauche de la modale. `origineDunElementFixe()` (booking-admin.js) cherche `transform`, `scale`, `rotate`, `translate`, `filter`, `perspective` et `contain`, et rend l'origine à soustraire.

**La racine.** Dans une méthode d'un `Alpine.data`, `$el` désigne l'élément qui **évalue** l'expression — le bouton quand la méthode est appelée depuis son `x-on:click` — et non la racine du composant. Pour mesurer la rangée entière, `$root`.

Un panneau fixe ne suit pas son déclencheur : le refermer sur `scroll` et `resize`. Et ne pas le téléporter vers `body` s'il contient des `$wire.*` : il quitterait la racine Livewire.
