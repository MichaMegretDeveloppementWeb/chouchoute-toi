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

## Dans une modale du kit, click.outside est mort sans .capture
Le panneau blanc de `<x-ui.modal>` porte `@click.stop` (vendor/falcon/ui-kit/.../ui/modal.blade.php). Alpine enregistre `click.outside` sur `document` **en phase de bulle** : aucun clic tombant dans la modale ne l'atteint donc jamais. La directive n'est pas cassée, elle est hors de portée, et rien ne le signale.

Symptôme : une liste déroulante qui ne se ferme que si l'on clique dans la marge autour du panneau blanc. Trois panneaux du formulaire de rendez-vous en souffraient ; deux le masquaient par leur `focusout`.

Correctif : ajouter `.capture` (`x-on:click.outside.capture`). La capture descend du document vers la cible, donc elle passe avant le `stopPropagation`.

Quand le « dedans » n'est pas tout `$el` — une rangée qui porte d'autres champs que le déclencheur et son panneau — écouter `click.window.capture` et tester soi-même la containment sur deux `x-ref`.

Échap : la modale écoute `keydown.escape.window`. Un panneau qui stoppe à vide empêche la modale de se refermer. Ne stopper que s'il y avait quelque chose à fermer : `x-on:keydown.escape="if (ouvert) { $event.stopPropagation(); ouvert = false }"`.

Diagnostic : poser deux écouteurs sur document, un en capture et un en bulle, et cliquer. Vu en capture seulement = le stopPropagation est en cause.

## Un composant Alpine né d'un rendu Livewire relit ses données sur fb-morphe
Un `x-data` ne se réévalue jamais de lui-même. Quand ses données viennent du HTML rendu par Livewire (un bloc `<script type="application/json">`, un `<input type="hidden">`), il les relit dans une méthode `lire()` appelée à `init()` **et** sur `x-on:fb-morphe.window="lire()"`. `booking-admin.js` émet `fb-morphe` à la fin de chaque morph ; un `Livewire.hook('morphed')` posé dans `init()` arriverait après le morph en cours et survivrait au composant.

Deux pièges mesurés : le morph **vide** un `<input type="hidden">` qui n'a pas de `value` côté serveur, quoi que Livewire y ait écrit avant — rendre `value="{{ $value }}"` ; et les lignes clonées d'un `x-for` ne sont pas retrouvées dans le HTML serveur — la liste va sous `wire:ignore`, nourrie par le bloc JSON posé hors du `wire:ignore`.

Un anneau `ring-inset` est une ombre intérieure : le fond d'un enfant la recouvre. Une boîte qui contient une cellule à fond (l'unité `min` / `€`) marque son focus par `border`, pas par `ring`.

## Rien n'interpole une propriété Livewire dans un x-data du formulaire
`<x-ui.modal>` montre son contenu par `x-show`, jamais par `x-if` : le sous-arbre du formulaire est dans le document dès le premier rendu de la page, et Alpine ne réévalue jamais un `x-data` sur un élément qui existe déjà. Un `{{ $selectedId }}` écrit dans un `x-data` reste donc **à la valeur qu'il avait au chargement de la page**, quoi que le serveur ait fait depuis.

Deux pannes vécues : « Venu » appelait `complete(null)` (TypeError sur le fil, invisible aux essais qui appellent la méthode avec un identifiant), et le champ de battement restait ouvert d'un formulaire à l'autre.

La règle : on lit `$wire.<propriété>` **au moment de l'appel**. Un état Alpine qui dépend du serveur se recalcule sur `x-on:open-modal.window`, l'ouverture arrivant toujours après l'appel Livewire qui la précède (`ouvrirLaSaisie` et `ouvrirLEntree` l'attendent).

Un essai qui appelle `->call('complete', $id)` court-circuite le Blade et ne voit rien. Le seul garde-fou est une assertion sur le rendu, comme `assertSeeHtml('$wire[action]($wire.selectedId)')`.
