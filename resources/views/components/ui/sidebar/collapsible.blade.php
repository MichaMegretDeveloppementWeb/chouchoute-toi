{{--
    A section of the sidebar that folds its links away.

    Publie depuis falcon/ui-kit. Trois choses ont change, et elles se tiennent.

    **Le rail ne se deploie plus au survol.** Il gardait 62 px au repos et
    passait a 260 sous la souris, ce qui faisait apparaitre les sous-menus dans
    le flux : l'entree qu'on visait descendait de 182 px pendant qu'on avancait
    vers elle. La largeur ne depend plus que du bouton de la barre du haut.

    **Une section replie ouvre donc un volet.** Sur le rail, le bouton du kit ne
    faisait rien de visible : il basculait un accordeon dont la liste etait en
    `display: none`. Il ouvre maintenant ses liens dans un panneau ancre a sa
    hauteur, a droite du rail.

    Le volet est teleporte vers `body` : la barre est en `overflow-clip`, et il y
    serait rogne. Il ne contient que des liens, donc rien qui ait besoin de
    rester dans la racine Livewire.

    **L'en-tete mene quelque part.** Ouvrir une section ou l'on n'est pas ne
    menait nulle part : il fallait ensuite viser un second lien. Le libelle est
    donc un lien vers `href`, la premiere page de la section, tant qu'on n'y est
    pas ; une fois dedans il redevient une bascule, n'ayant plus rien a mener.
    Rien n'ouvre la section apres la navigation : y arriver la rend active, et
    `init()` l'ouvre. Le chevron, lui, ne fait jamais que replier et deplier.

    Sans `href`, le composant se comporte comme celui du kit.

    Children carry no icon: the icon belongs to the section, and is what names it
    when the bar is collapsed to its rail.
--}}
@props([
    'label',
    'icon' => null,
    'href' => null,
    'active' => false,
    'open' => false,
    'name' => null,
    'persist' => true,
])

@php
$key = 'ui-sidebar-'.($name ?: \Illuminate\Support\Str::slug($label));

// Remembered across navigations, because the links are plain anchors: without
// this the whole bar would refold on every page.
$state = $persist
    ? '$persist('.($open ? 'true' : 'false').").as('{$key}')"
    : ($open ? 'true' : 'false');

// La section ou l'on se trouve deja n'a plus rien a mener : son en-tete
// redevient la bascule du kit.
$leadsSomewhere = $href !== null && ! $active;

// Ces classes decrivent la barre deployee ; la feuille de l'hote les replie
// par leur nom quand `data-fb-sidebar` vaut « repliee ».
$labelClass = 'fb-sidebar-label whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';

// Same geometry as a link, down to the max-width: it is what keeps the active
// pill a 38 pixel square in the rail instead of a shape cut off at 62.
$rowBase = 'fb-sidebar-link group flex w-full items-center rounded-lg px-2.5 py-[7px] max-sm:py-3 text-[13px] font-medium gap-x-3 max-w-full transition-[max-width,gap,background-color,color] duration-300 ease-in-out';

// La pastille dit ou l'on est quand l'enfant actif est cache, c'est-a-dire sur
// le rail. Ecrite en PHP plutot qu'en empilant des variantes, pour ne rien
// devoir a l'ordre dans lequel Tailwind les trie.
$rowState = $active
    ? 'fb-sidebar-badge text-primary'
    : 'text-secondary hover:bg-elevated hover:text-primary';

// `fb-sidebar-gap` : la feuille annule cette gouttiere sur le rail, comme elle
// le fait pour la rangee. Sans lui, les douze pixels subsistent devant un
// libelle de largeur nulle et la pastille deborde des trente-huit.
$labelPart = 'fb-sidebar-gap flex min-w-0 flex-1 items-center gap-x-3 text-left';

$iconState = $active
    ? 'fb-sidebar-badge-icon text-primary'
    : 'text-muted group-hover:text-secondary';
@endphp

{{-- x-id is not optional: without a root declaring the name, $id() caches per
     element and hands the button and the panel two different numbers. Its
     counter being global is also what makes the two renderings of the bar,
     mobile and desktop, come out with distinct ids.

     `pointerdown` sert de repli a `mener()` : `click` n'est pas partout un
     `PointerEvent`, et il faut savoir si le geste vient du doigt. --}}
<li x-data="barreSection({{ $state }}, {{ $active ? 'true' : 'false' }})"
    x-id="['ui-sidebar-sous-menu']"
    x-on:mouseenter="viser()"
    x-on:mouseleave="quitter()"
    x-on:pointerdown="pointeur = $event.pointerType"
    class="relative">

    {{-- La rangee porte la pastille ; les deux commandes vivent dedans. Un
         `<button>` ne peut pas tenir dans un `<a>`, ce sont donc des freres. --}}
    <div x-ref="declencheur" {{ $attributes->merge(['class' => "$rowBase $rowState"]) }}>
        @if ($leadsSomewhere)
            <a href="{{ $href }}" x-on:click="mener($event)" class="{{ $labelPart }}">
        @else
            <button type="button" x-on:click="basculer()" class="{{ $labelPart }}">
        @endif

            @if($icon)
                <x-ui.icon :name="$icon" class="h-[18px] w-[18px] shrink-0 {{ $iconState }}" aria-hidden="true" />
            @endif

            <span class="{{ $labelClass }}">{{ $label }}</span>

        @if ($leadsSomewhere)
            </a>
        @else
            </button>
        @endif

        {{-- Le chevron ne fait que replier et deplier, et c'est lui qui annonce
             l'etat. Il se replie avec le libelle plutot que de depasser du rail,
             et sa rotation ne partage pas la transition de celui-ci.

             `-my-3` sous 640 : la cible fait quarante-quatre pixels de cote sans
             que la rangee grandisse pour autant. --}}
        <button type="button"
            x-on:click="basculer()"
            x-on:keydown.escape="fermerLeVolet()"
            :aria-expanded="rail() ? volet : ouvert"
            :aria-controls="$id('ui-sidebar-sous-menu')"
            aria-label="Sous-menu {{ $label }}"
            class="flex shrink-0 items-center justify-center max-sm:-my-3 max-sm:h-11 max-sm:w-11 {{ $labelClass }}">
            <x-ui.icon name="chevron-down"
                class="h-4 w-4 transition-transform duration-200"
                ::class="ouvert ? 'rotate-180' : ''"
                aria-hidden="true" />
        </button>
    </div>

    {{-- L'accordeon, quand la barre est deployee. --}}
    <div x-show="ouvert" x-collapse x-cloak :id="$id('ui-sidebar-sous-menu')">
        {{-- Hidden rather than faded in the rail: display:none also takes the
             links out of the tab order and out of the accessibility tree, which
             opacity would not.

             The rule is left of the section icon by one pixel of border plus
             its padding, which lands the children's text on the exact column of
             the parent's label. --}}
        <ul class="fb-sidebar-submenu mt-0.5 block space-y-0.5 pb-1 ml-[19px] border-l border-base pl-2.5">
            {{ $slot }}
        </ul>
    </div>

    {{-- Le volet du rail. Teleporte : la barre rognerait un panneau pose a
         l'interieur, et le sien ne contient que des liens. --}}
    <template x-teleport="body">
        {{-- L'element se donne au composant plutot que par `x-ref` : teleporte
             sous `body`, il ne remonte plus jusqu'a la racine qui tient les
             references, et `$refs.volet` restait vide. --}}
        <div x-show="volet" x-cloak x-init="panneau = $el"
            x-on:mouseenter="garder()"
            x-on:mouseleave="quitter()"
            x-on:keydown.escape.window="fermerLeVolet()"
            x-on:scroll.window="fermerLeVolet()"
            x-bind:style="`top: ${haut}px; left: ${gauche}px`"
            role="group" aria-label="{{ $label }}"
            class="fb-sidebar-panel fixed z-[60] w-[220px] rounded-xl border border-base bg-surface p-2 shadow-lg">

            <p class="px-2.5 pb-1.5 pt-1 text-[11px] font-semibold uppercase tracking-wider text-muted">{{ $label }}</p>

            <ul class="space-y-0.5">
                {{ $slot }}
            </ul>
        </div>
    </template>
</li>
