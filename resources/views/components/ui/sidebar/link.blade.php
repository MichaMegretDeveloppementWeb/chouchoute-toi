{{--
    Published from falcon/ui-kit: the label folds by its class rather than by
    screen-width variants, and the link carries its title for the tooltip the
    sidebar shows when it is on its rail.
--}}
@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
])

@php
$labelClass = 'fb-sidebar-label whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';
// `max-sm:py-3`: below 640 the sidebar is a drawer aimed at with a finger, and
// 34px of height is under what a finger reaches without a second try.
$navLinkBase = 'fb-sidebar-link group flex items-center rounded-lg px-2.5 py-[7px] max-sm:py-3 text-[13px] font-normal gap-x-3 max-w-full transition-[max-width,gap,background-color,color] duration-300 ease-in-out';
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

         `data-fb-title`: on the rail the icon stands alone and says nothing to
         whoever does not know it yet. One tooltip serves the whole sidebar,
         from the sidebar's side; putting one on each link made dozens of them,
         the sidebar being rendered twice and each section rendering its links
         twice more. --}}
    <a href="{{ $href }}" data-fb-title="{{ trim($slot) }}"
        @if($active) aria-current="page" @endif
        {{ $attributes->merge(['class' => "$navLinkBase $stateClass"]) }}>
        @if($icon)
            <x-ui.icon :name="$icon" class="h-[18px] w-[18px] shrink-0 {{ $iconClass }}" />
        @endif
        <span class="{{ $labelClass }}">{{ $slot }}</span>
    </a>
</li>
