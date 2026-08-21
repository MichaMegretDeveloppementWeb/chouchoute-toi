---
paths:
  - 'packages/falcon-booking/src/Livewire/**'
---

# Livewire

## Une alerte ajoutée pendant le rendu arrive après la ligne qui l'affiche
Une propriété `#[Computed]` qui attrape une lecture ratée et appelle `addError('x-load-failed', …)` n'est évaluée qu'au moment où le gabarit la lit. Si le `@error('x-load-failed')` est écrit **au-dessus** du contenu — c'est sa place, puisque l'alerte le remplace — la ligne est déjà partie quand l'erreur s'enregistre : l'écran revient vide, sans un mot, et seul un test qui assertionne le message le voit. `assertHasErrors` passe pourtant : l'erreur est bien là, elle n'est simplement plus affichable.

Le correctif : lire dans `render()`, avant que la vue soit construite. Le trait `Concerns/ReadsBeforeRendering` donne un nom à ce geste (`$this->readBeforeRendering($this->sections)`) — une expression nue `$this->sections;` serait par ailleurs refusée par PHPStan (`expr.resultUnused`).

Corollaire de doctrine : l'alerte **remplace** le contenu, elle ne s'affiche pas à côté. Une liste vide dit « il n'y a rien », qui n'est pas « la liste n'a pas pu être lue » — sur le Journal, c'est la différence entre deux réponses opposées.
