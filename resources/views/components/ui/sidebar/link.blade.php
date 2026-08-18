{{--
    Publie depuis falcon/ui-kit : le libelle se replie par sa classe plutot que
    par des variantes de largeur d'ecran, et le lien porte son titre pour
    l'infobulle que la barre affiche quand elle est sur son rail.
--}}
@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
])

@php
$labelClass = 'fb-barre-libelle whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';
$navLinkBase = 'fb-barre-lien group flex items-center rounded-lg px-2.5 py-[7px] text-[13px] font-normal gap-x-3 max-w-full transition-[max-width,gap,background-color,color] duration-300 ease-in-out';
$stateClass = $active
    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200';
$iconClass = $active
    ? 'text-white dark:text-gray-900'
    : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300';
@endphp

<li>
    {{-- aria-current, not the class alone: the colours say where we are to
         whoever sees them, and nothing said it to anyone else.

         `data-fb-titre` : sur le rail, l'icone est seule et ne dit rien a qui
         ne la connait pas encore. Une seule infobulle sert toute la barre, du
         cote de celle-ci ; la poser sur chaque lien en faisait quatre-vingt-
         quatorze, la barre etant rendue deux fois et chaque section rendant ses
         liens deux fois de plus. --}}
    <a href="{{ $href }}" data-fb-titre="{{ trim($slot) }}"
        @if($active) aria-current="page" @endif
        {{ $attributes->merge(['class' => "$navLinkBase $stateClass"]) }}>
        @if($icon)
            <x-ui.icon :name="$icon" class="h-[18px] w-[18px] shrink-0 {{ $iconClass }}" />
        @endif
        <span class="{{ $labelClass }}">{{ $slot }}</span>
    </a>
</li>
