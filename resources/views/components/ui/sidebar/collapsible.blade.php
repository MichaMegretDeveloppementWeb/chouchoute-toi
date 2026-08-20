{{--
    A section of the sidebar that folds its links away.

    Publie depuis falcon/ui-kit. Deux choses ont change, et elles se tiennent.

    **Le rail ne se deploie plus au survol.** Il gardait 62 px au repos et
    passait a 260 sous la souris, ce qui faisait apparaitre les sous-menus dans
    le flux : l'entree qu'on visait descendait de 182 px pendant qu'on avancait
    vers elle. La largeur ne depend plus que du bouton de la barre du haut.

    **Une section replie ouvre donc un volet.** Sur le rail, le bouton du kit ne
    faisait rien de visible : il basculait un accordeon dont la liste etait en
    `display: none`. Il ouvre maintenant ses liens dans un panneau ancre a sa
    hauteur, a droite du rail. C'est ce que font Jira, GitLab et les menus
    replies d'Ant Design ou d'Element Plus.

    Le volet est teleporte vers `body` : la barre est en `overflow-clip`, et il y
    serait rogne. Il ne contient que des liens, donc rien qui ait besoin de
    rester dans la racine Livewire.

    Children carry no icon: the icon belongs to the section, and is what names it
    when the bar is collapsed to its rail.
--}}
@props([
    'label',
    'icon' => null,
    'active' => false,
    'open' => false,
    'name' => null,
    'persist' => true,
])

@php
$cle = 'ui-sidebar-'.($name ?: \Illuminate\Support\Str::slug($label));

// Remembered across navigations, because the links are plain anchors: without
// this the whole bar would refold on every page.
$etat = $persist
    ? '$persist('.($open ? 'true' : 'false').").as('{$cle}')"
    : ($open ? 'true' : 'false');

// Ces classes decrivent la barre deployee ; la feuille de l'hote les replie
// par leur nom quand `data-fb-sidebar` vaut « repliee ».
$labelClass = 'fb-sidebar-label whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';

// Same geometry as a link, down to the max-width: it is what keeps the active
// pill a 38 pixel square in the rail instead of a shape cut off at 62.
$boutonBase = 'fb-sidebar-link group flex w-full items-center rounded-lg px-2.5 py-[7px] max-sm:py-3 text-[13px] font-medium gap-x-3 max-w-full transition-[max-width,gap,background-color,color] duration-300 ease-in-out';

// La pastille dit ou l'on est quand l'enfant actif est cache, c'est-a-dire sur
// le rail. Ecrite en PHP plutot qu'en empilant des variantes, pour ne rien
// devoir a l'ordre dans lequel Tailwind les trie.
$boutonEtat = $active
    ? 'fb-sidebar-badge text-primary'
    : 'text-secondary hover:bg-elevated hover:text-primary';

$iconeEtat = $active
    ? 'fb-sidebar-badge-icon text-primary'
    : 'text-muted group-hover:text-secondary';
@endphp

{{-- x-id is not optional: without a root declaring the name, $id() caches per
     element and hands the button and the panel two different numbers. Its
     counter being global is also what makes the two renderings of the bar,
     mobile and desktop, come out with distinct ids. --}}
<li x-data="barreSection({{ $etat }}, {{ $active ? 'true' : 'false' }})"
    x-id="['ui-sidebar-sous-menu']"
    x-on:mouseenter="viser()"
    x-on:mouseleave="quitter()"
    class="relative">

    <button type="button"
        x-ref="declencheur"
        x-on:click="basculer()"
        x-on:keydown.escape="fermerLeVolet()"
        :aria-expanded="rail() ? volet : ouvert"
        :aria-controls="$id('ui-sidebar-sous-menu')"
        {{ $attributes->merge(['class' => "$boutonBase $boutonEtat"]) }}>

        @if($icon)
            <x-ui.icon :name="$icon" class="h-[18px] w-[18px] shrink-0 {{ $iconeEtat }}" aria-hidden="true" />
        @endif

        <span class="flex-1 text-left {{ $labelClass }}">{{ $label }}</span>

        {{-- Wrapped, so the chevron folds away with the label instead of
             sticking out of the rail, and so its rotation does not share a
             transition with the label's own. --}}
        <span class="shrink-0 {{ $labelClass }}">
            <x-ui.icon name="chevron-down"
                class="h-4 w-4 transition-transform duration-200"
                ::class="ouvert ? 'rotate-180' : ''"
                aria-hidden="true" />
        </span>
    </button>

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
