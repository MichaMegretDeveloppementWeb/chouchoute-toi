---
paths:
  - 'packages/falcon-booking/src/Livewire/**'
---

# Livewire

## Une alerte ajoutée pendant le rendu arrive après la ligne qui l'affiche
Une propriété `#[Computed]` qui attrape une lecture ratée et appelle `addError('x-load-failed', …)` n'est évaluée qu'au moment où le gabarit la lit. Si le `@error('x-load-failed')` est écrit **au-dessus** du contenu — c'est sa place, puisque l'alerte le remplace — la ligne est déjà partie quand l'erreur s'enregistre : l'écran revient vide, sans un mot, et seul un test qui assertionne le message le voit. `assertHasErrors` passe pourtant : l'erreur est bien là, elle n'est simplement plus affichable.

Le correctif : lire dans `render()`, avant que la vue soit construite. Le trait `Concerns/ReadsBeforeRendering` donne un nom à ce geste (`$this->readBeforeRendering($this->sections)`) — une expression nue `$this->sections;` serait par ailleurs refusée par PHPStan (`expr.resultUnused`).

Corollaire de doctrine : l'alerte **remplace** le contenu, elle ne s'affiche pas à côté. Une liste vide dit « il n'y a rien », qui n'est pas « la liste n'a pas pu être lue » — sur le Journal, c'est la différence entre deux réponses opposées.

## Un même refus ne se dit qu'une fois, et avec le bon chiffre
Un plafond fixe sur un champ, doublé plus loin d'un contrôle exact, donne deux messages et deux chiffres pour une seule erreur.

Mesuré sur le nombre de répétitions : `max:732` répondait « au plus 732 » à 4000, puis le contrôle d'horizon répondait « 104 au plus » à 105. Le premier chiffre n'a jamais rien voulu dire pour le motif à l'écran.

La règle du champ porte la mesure exacte (`repetitionsThatFitTheHorizon()`), calculée sur un motif d'une répétition — au-delà du plafond structurel le domaine refuse de construire la règle et il ne resterait rien à mesurer. Le contrôle en aval ne garde que le cas qu'aucune règle de champ ne sait exprimer : une fin par **date**, où ce qui compte est où tombe la dernière occurrence, pas la date saisie.

Le plafond de `RecurrenceData` reste, mais comme invariant du domaine, pas comme message.
