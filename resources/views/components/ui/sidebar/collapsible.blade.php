{{--
    A section of the sidebar that folds its links away. Published from
    falcon/ui-kit, with three changes that hold together.

    The rail never unfolds on hover: the width answers to the topbar button
    alone, at every screen width.

    A folded section therefore opens a panel, teleported to `body` because the
    sidebar is `overflow-clip`. It holds links only, so nothing that needs to
    stay inside the Livewire root.

    The header leads somewhere: while we are not in the section, its label is a
    link to `href`; once inside it becomes the toggle again, having nothing left
    to lead to. Without `href` the component behaves like the kit's. The chevron
    only ever folds and unfolds.

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

$leadsSomewhere = $href !== null && ! $active;

// These classes describe the sidebar expanded; the host's stylesheet folds each
// of them by name when `data-fb-sidebar` says so.
$labelClass = 'fb-sidebar-label whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';

// Same geometry as a link, down to the max-width: it is what keeps the active
// pill a 38 pixel square in the rail instead of a shape cut off at 62.
$rowBase = 'fb-sidebar-link group flex w-full items-center rounded-lg px-2.5 py-[7px] max-sm:py-3 text-[13px] font-medium gap-x-3 max-w-full transition-[max-width,gap,background-color,color] duration-300 ease-in-out';

// The pill says where we are when the active child is hidden, which is on the
// rail. Written in PHP rather than stacked variants, so it owes nothing to the
// order Tailwind sorts them in.
$rowState = $active
    ? 'fb-sidebar-badge text-primary'
    : 'text-secondary hover:bg-elevated hover:text-primary';

// `fb-sidebar-gap`: the stylesheet cancels this gutter on the rail, as it does
// for the row. Without it the twelve pixels survive in front of a zero-width
// label and the pill overflows its thirty-eight.
$labelPart = 'fb-sidebar-gap flex min-w-0 flex-1 items-center gap-x-3 text-left';

$iconState = $active
    ? 'fb-sidebar-badge-icon text-primary'
    : 'text-muted group-hover:text-secondary';
@endphp

{{-- x-id is not optional: without a root declaring the name, $id() caches per
     element and hands the button and the panel two different numbers. Its
     counter being global is also what makes the two renderings of the bar,
     mobile and desktop, come out with distinct ids.

     `pointerdown` is the fallback for `lead()`: `click` is not a
     `PointerEvent` everywhere, and the gesture's source has to be known. --}}
<li x-data="sidebarSection({{ $state }}, {{ $active ? 'true' : 'false' }})"
    x-id="['ui-sidebar-submenu']"
    x-on:mouseenter="aim()"
    x-on:mouseleave="leave()"
    x-on:pointerdown="pointerType = $event.pointerType"
    class="relative">

    {{-- The row carries the pill and both controls live inside it. A `<button>`
         cannot sit inside an `<a>`, so they are siblings. --}}
    <div x-ref="trigger" {{ $attributes->merge(['class' => "$rowBase $rowState"]) }}>
        @if ($leadsSomewhere)
            <a href="{{ $href }}" x-on:click="lead($event)" class="{{ $labelPart }}">
        @else
            <button type="button" x-on:click="toggle()" class="{{ $labelPart }}">
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

        {{-- The chevron announces the state. It folds away with the label
             rather than overflow the rail, and its rotation does not share the
             label's transition.

             `-my-3` below 640: the target is forty-four pixels a side without
             the row growing with it. --}}
        <button type="button"
            x-on:click="toggle()"
            x-on:keydown.escape="closePanel()"
            :aria-expanded="isRail() ? isPanelOpen : isOpen"
            :aria-controls="$id('ui-sidebar-submenu')"
            aria-label="Sous-menu {{ $label }}"
            class="flex shrink-0 items-center justify-center max-sm:-my-3 max-sm:h-11 max-sm:w-11 {{ $labelClass }}">
            <x-ui.icon name="chevron-down"
                class="h-4 w-4 transition-transform duration-200"
                ::class="isOpen ? 'rotate-180' : ''"
                aria-hidden="true" />
        </button>
    </div>

    {{-- The accordion, when the sidebar is expanded. --}}
    <div x-show="isOpen" x-collapse x-cloak :id="$id('ui-sidebar-submenu')">
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

    {{-- The rail's panel, teleported: the sidebar would clip a panel laid
         inside it, and this one holds links only. --}}
    <template x-teleport="body">
        {{-- The element hands itself to the component rather than through an
             `x-ref`: teleported under `body`, it no longer climbs back to the
             root that holds the refs, and `$refs.panel` stayed empty. --}}
        <div x-show="isPanelOpen" x-cloak x-init="panel = $el"
            x-on:mouseenter="keep()"
            x-on:mouseleave="leave()"
            x-on:keydown.escape.window="closePanel()"
            x-on:scroll.window="closePanel()"
            x-bind:style="`top: ${top}px; left: ${left}px`"
            role="group" aria-label="{{ $label }}"
            class="fb-sidebar-panel fixed z-[60] w-[220px] rounded-xl border border-base bg-surface p-2 shadow-lg">

            <p class="px-2.5 pb-1.5 pt-1 text-[11px] font-semibold uppercase tracking-wider text-muted">{{ $label }}</p>

            <ul class="space-y-0.5">
                {{ $slot }}
            </ul>
        </div>
    </template>
</li>
