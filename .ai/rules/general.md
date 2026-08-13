---
paths:
  - '**/*.blade.php'
---

# General

## x-ui.toggle est décoratif : il n'écoute pas wire:model
`<x-ui.toggle>` du kit garde son état dans un `x-data="{ on: ... }"` local, initialisé une seule fois par la prop `checked`. Il n'a aucun lien avec Livewire : un `wire:model` posé dessus atterrit sur la div extérieure et ne fait rien.

Conséquence observée : un réglage valant `true` côté serveur s'affichait « éteint », ce qui invite l'utilisatrice à le réactiver alors qu'il l'est déjà.

Pour un booléen lié à Livewire, utiliser `<x-ui.checkbox wire:model="...">`, qui se lie correctement. Si l'apparence d'interrupteur est nécessaire, passer `:checked="$valeur"` pour l'état initial ET brancher le clic sur `$wire` explicitement — la prop seule ne suffit jamais.
