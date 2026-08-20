---
paths:
  - 'resources/views/components/ui/**'
---

# Ui

## La barre latérale : deux états, aucun survol, et un volet sur le rail
Les composants de `components/ui/sidebar/` sont **publiés** depuis falcon/ui-kit et divergent : ne pas les remplacer par `ui-kit:publish`.

Le kit faisait dépendre la largeur de la barre de celle de l'écran, avec un `:hover` qui déployait le rail. Mesuré : l'entrée visée descendait de 182 px pendant qu'on avançait vers elle, et le bouton d'une section repliée ne faisait rien de visible, sa liste étant en `display: none`.

Le modèle est maintenant : **ouverte ou repliée, à toutes les largeurs ≥ lg, au bouton seul**. L'état vit sur `<html>` en `data-fb-sidebar`, posé avant la peinture par le layout ; par défaut repliée sous 1500 px, ouverte au-delà, et ce défaut n'est pas retenu pour qu'un autre écran retrouve le sien.

Les composants décrivent la barre **déployée**. `resources/css/ui-kit.css` la replie par les classes `fb-sidebar`, `fb-sidebar-label`, `fb-sidebar-link`, `fb-sidebar-gap`, `fb-sidebar-submenu`, `fb-sidebar-title`, `fb-sidebar-badge`, scopées à `.fb-sidebar` pour que le tiroir du téléphone garde ses libellés. `--fb-rail` porte l'état jusqu'à Alpine : c'est la feuille qui décide, jamais un `matchMedia` recopié.

Sur le rail, une section ouvre un **volet flottant** ancré à son icône, et la barre affiche **une seule** infobulle pour tous ses liens (`data-fb-title`) : posée sur chaque lien, elle était rendue 94 fois, la barre étant rendue deux fois et chaque section rendant ses liens une fois de plus dans son volet.
