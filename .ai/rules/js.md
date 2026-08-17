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
Deux pièges rencontrés en sortant un catalogue déroulant du conteneur de défilement de la modale. Ce catalogue a depuis été remplacé par une modale par-dessus (`service-picker-modal`), parce qu'un panneau fixe doit se refermer au défilement, ce qui est insupportable dès que la liste est longue. Les deux pièges restent vrais pour qui repasserait par un panneau fixe.

**L'origine.** Un `position: fixed` se place contre la fenêtre, sauf si un ancêtre établit un bloc conteneur. Le panneau des modales du kit porte `scale-100` à l'ouverture, et Tailwind 4 l'écrit sur la propriété **`scale`**, pas sur `transform` : une sonde qui ne teste que `transform` ne le voit pas. Le panneau atterrissait décalé de tout le coin supérieur gauche de la modale. Chercher `transform`, `scale`, `rotate`, `translate`, `filter`, `perspective` et `contain` sur les ancêtres, et soustraire l'origine trouvée.

**La racine.** Dans une méthode d'un `Alpine.data`, `$el` désigne l'élément qui **évalue** l'expression — le bouton quand la méthode est appelée depuis son `x-on:click` — et non la racine du composant. Pour mesurer la rangée entière, `$root`.

Un panneau fixe ne suit pas son déclencheur : le refermer sur `scroll` et `resize`. Et ne pas le téléporter vers `body` s'il contient des `$wire.*` : il quitterait la racine Livewire. Quand la liste peut être longue, préférer une seconde modale.

## Une modale par-dessus une autre : la pile décide, et toute fermeture passe par close-modal
`PILE_DES_MODALES` (booking-admin.js) suit les modales ouvertes et marque celle du dessus par `data-fb-modale-dessus`. La modale publiée (`resources/views/components/ui/modal.blade.php`) ne réagit à Échap que si elle porte ce marqueur : Échap ferme la sélection de prestation, pas le formulaire dessous.

Cela ne tient que si **chaque** fermeture émet `close-modal` : Échap, le voile et le bouton fermer appellent `fermer()`, qui émet l'événement. Un `open = false` posé à la main laisse la modale dans la pile, le focus n'est pas rendu et le piège de tabulation tourne dans une racine invisible.

À l'ouverture, le focus va sur le panneau, sauf si un élément porte `data-fb-autofocus` (la recherche d'une modale qui n'est qu'une recherche). Un `focus()` posé dans un `$nextTick` arrive avant que `x-show` n'affiche le panneau et ne fait rien.
