---
paths:
  - 'packages/falcon-booking/resources/views/**'
---

# Views

## Tout élément cliquable porte cursor: pointer
Le navigateur donne `cursor: default` aux `<button>` : sans règle, un bouton passe pour du décor.

La règle générale vit dans `packages/falcon-booking/resources/css/booking-admin.css` et couvre `button`, `summary`, `a[href]`, `label[for]`, `select`, `[role=button]`, `[role=menuitem]`, ainsi que ce qui n'est cliquable que par attribut : `[wire:click]`, `[x-on:click]`, `[@click]`. Les éléments désactivés reçoivent `cursor: not-allowed`.

Ne pas ajouter `cursor-pointer` classe par classe sur les nouveaux boutons : la règle générale s'en charge. Si un élément cliquable n'est visé par aucun de ces sélecteurs (une div rendue interactive en JS, par exemple), l'ajouter à la règle plutôt qu'au composant.

## wire:model.blur ne convient qu'aux champs de saisie
La doctrine `livewire-validation.md` §4 impose `wire:model.blur` par défaut. Elle vise les **champs de saisie** : son exemple est un `<input type="text">`.

**Ne jamais poser `.blur` sur une case à cocher, un select ou un radio.** Cocher une case ne produit pas de blur exploitable avant l'action : la valeur n'est jamais transmise, l'enregistrement écrit l'ancienne, et rien ne le signale. Vérifié sur l'écran des réglages : le champ texte partait bien, la case restait à sa valeur d'origine en base.

Pour ces contrôles, `wire:model` nu est le bon choix : en Livewire 3+ il est différé et se synchronise avec la prochaine action, ce qui est exactement le comportement attendu dans un formulaire.

`.live.debounce` reste réservé à la recherche.

**Piège de vérification** : les tests Livewire posent les propriétés directement (`->set()`) et court-circuitent la liaison, donc ils restent verts. Seul un vrai clic dans le navigateur, suivi d'une lecture en base, le montre. Les événements DOM synthétiques (`dispatchEvent(new Event('input'))` puis `.blur()`) ne sont pas fiables non plus.

## Une classe de largeur sur un champ du kit ne fait rien
Les champs du kit (`x-ui.input`, `x-ui.select`, `x-ui.textarea`) portent `block w-full` dans leurs classes de base. Une classe passée par l'appelant est fusionnée *après* dans l'attribut, mais `w-full` et `w-48` ont la même spécificité : c'est l'ordre dans la feuille de style générée qui tranche, pas l'ordre dans l'attribut. La largeur demandée est donc ignorée, sans erreur ni avertissement.

Mesuré sur le filtre du Journal : `class="w-48"` rendait un champ de 1392 px au lieu de 192.

La largeur va sur un bloc autour du champ, jamais sur le champ. `x-ui.search-input` expose `wrapper-class` exactement pour ça ; les autres champs n'ont pas d'équivalent, donc on écrit le bloc à la main.

Et penser à `npm run build` : une combinaison de classes jamais utilisée ailleurs (`sm:w-48`) n'existe pas encore dans le CSS, et la correction semble sans effet tant qu'on n'a pas régénéré.
