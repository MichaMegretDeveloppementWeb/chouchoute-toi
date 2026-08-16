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

## Vérifier une vue sous 640 px : une iframe, pas un redimensionnement
Chrome ignore un redimensionnement programmatique de fenêtre quand elle est maximisée : l'outil répond « success » et rien ne bouge.

Les media queries s'évaluent sur le viewport de l'**iframe**, pas sur celui de la fenêtre. Une iframe de 412 px posée dans n'importe quel onglet rend donc la vue mobile, en même origine, donc entièrement mesurable et pilotable (`f.contentWindow.Alpine`, `f.contentDocument`).

```js
const f = document.createElement('iframe');
f.src = '/admin/agenda';
f.style.cssText = 'width:412px;height:820px;border:0';
document.body.appendChild(f);
await new Promise(r => f.onload = r);
```

## Jamais de directive Blade dans un commentaire Blade
Blade compile les directives **avant** de retirer les commentaires. Un `@php`, un `@if` ou un `@foreach` cité entre `{{-- --}}` est donc bel et bien compilé.

Le cas mesuré : le mot `@php` écrit entre accents graves dans un commentaire a ouvert un bloc PHP qui a avalé **230 lignes** de template, jusqu'au premier `@endphp` réel. Symptôme : `ParseError: unexpected token "endforeach", expecting "elseif" or "else" or "endif"`, pointant une ligne parfaitement équilibrée, très loin de la cause.

Un compteur d'équilibre écrit à la main ne le voit pas non plus : il retire les commentaires d'abord, donc il déclare le fichier sain. Pour trouver la cause, comparer le **compilé** au source :

```php
file_put_contents('out.php', Blade::compileString(file_get_contents($vue)));
// puis : php -l out.php
```

Dans un commentaire, nommer la directive sans son `@` : « le bloc PHP d'en-tête », pas « le bloc `@php` ».

## Le bloc d'en-tête et les boucles partagent une seule portée
Un `@foreach (... as $valeur => $intitule)` écrase la variable `$valeur` définie plus haut dans le bloc PHP d'en-tête, pour tout le reste du fichier. Aucune erreur : la classe CSS rendue devient simplement la valeur de la dernière itération.

Repéré à l'écran par un `class="indisponibilite text-muted"` sur un `<p>`. Nommer les variables de boucle d'après ce qu'elles parcourent, jamais d'après un concept générique (`$valeur`, `$ligne`, `$champ`) déjà employé comme nom de style.

## Une classe de largeur sur un champ du kit ne fait rien
Les champs du kit (`x-ui.input`, `x-ui.select`, `x-ui.textarea`) portent `block w-full` dans leurs classes de base. Une classe passée par l'appelant est fusionnée *après* dans l'attribut, mais `w-full` et `w-48` ont la même spécificité : c'est l'ordre dans la feuille de style générée qui tranche, pas l'ordre dans l'attribut. La largeur demandée est donc ignorée, sans erreur ni avertissement.

Mesuré sur le filtre du Journal : `class="w-48"` rendait un champ de 1392 px au lieu de 192.

La largeur va sur un bloc autour du champ, jamais sur le champ. `x-ui.search-input` expose `wrapper-class` exactement pour ça ; les autres champs n'ont pas d'équivalent, donc on écrit le bloc à la main.

Et penser à `npm run build` : une combinaison de classes jamais utilisée ailleurs (`sm:w-48`) n'existe pas encore dans le CSS, et la correction semble sans effet tant qu'on n'a pas régénéré.

## Une classe ne s'annule pas en en ajoutant une autre du même utilitaire
Même cause que ci-dessus, et pas seulement pour le kit : elle vaut pour nos propres variables de classes. Écrire `{{ $base }} px-0` pour retirer le `px-4` de `$base` ne retire rien, et rien ne le signale. Les variantes ne se neutralisent pas non plus entre elles : `sm:pr-0` ne défait pas `sm:px-2.5`.

Trois cas mesurés dans le seul formulaire de rendez-vous :

| Écrit | Rendu | Conséquence |
| --- | --- | --- |
| `$rangeeNue` + `px-0` | `px-4` | barre de gestes à 16 px des bords, filet trop court |
| `$rangeeNue` + `gap-x-0` | `gap-x-3` | filet à 12 px du milieu entre deux cases égales |
| `$valeur` + `text-muted` | `text-primary` | l'heure de fin, calculée, s'affichait comme une valeur qu'on saisit |

Deux façons de s'en sortir : **extraire la mesure de ce qui varie** (`$mesure` sans couleur, `$texte = $mesure.' text-primary'`, le point d'appel écrit la sienne), ou **ne pas réutiliser la variable** quand l'élément en veut trop peu.

Un retrait négatif (`-mx-4`) est un autre utilitaire, donc il passe — mais il déplace la boîte au lieu de retirer le retrait, ce qui diffère dès qu'un filet ou un fond entre en jeu.
