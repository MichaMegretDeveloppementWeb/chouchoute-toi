---
paths:
  - '**/*.blade.php'
---

# General

## falcon/ui-kit est une dépendance, pas du code du projet
Le kit s'installe par composer et vit dans `vendor/falcon/ui-kit`. On ne le clone pas dans `packages/`, on ne le republie pas en bloc, on ne le modifie jamais sur place : `vendor/` est réécrit au prochain `composer update`.

Trois cas, trois gestes, et rien d'autre :

| Le besoin | Le geste |
|---|---|
| Un composant du kit convient tel quel | l'utiliser via la dépendance, `<x-ui.…>` |
| Une modification légère d'un composant existant | `php artisan ui-kit:publish <composant>`, puis modifier la copie dans `resources/views/components/ui/` |
| Besoin radicalement différent, ou composant inexistant | écrire un composant du projet, **hors** de `components/ui/`, qui ne dépend pas du kit |

**Ne jamais publier un composant qu'on ne modifie pas.** Une copie identique à l'amont ne change rien au rendu mais fige la version : le kit évolue, la copie reste en arrière, et rien ne le signale. Mesuré le 2026-08-20 sur `sidebar/team` et `sidebar/trigger`, publiés et identiques à l'octet près — le premier n'était même utilisé nulle part. Les deux ont été dépubliés.

Dépublier est sans risque : le kit **ajoute** son dossier au view finder (`addLocation`) au lieu de l'imposer, donc `resources/views` passe d'abord et la résolution retombe sur le vendor dès qu'on retire le fichier. Vider `view:clear` après coup, le cache gardant le chemin résolu.

Les composants déjà publiés et réellement divergents sont documentés dans `.ai/rules/ui.md` : ne pas les écraser par un `ui-kit:publish`.

## x-ui.toggle est décoratif : il n'écoute pas wire:model
`<x-ui.toggle>` du kit garde son état dans un `x-data="{ on: ... }"` local, initialisé une seule fois par la prop `checked`. Il n'a aucun lien avec Livewire : un `wire:model` posé dessus atterrit sur la div extérieure et ne fait rien.

Conséquence observée : un réglage valant `true` côté serveur s'affichait « éteint », ce qui invite l'utilisatrice à le réactiver alors qu'il l'est déjà.

Pour un booléen lié à Livewire, utiliser `<x-ui.checkbox wire:model="...">`, qui se lie correctement. Si l'apparence d'interrupteur est nécessaire, passer `:checked="$valeur"` pour l'état initial ET brancher le clic sur `$wire` explicitement — la prop seule ne suffit jamais.
