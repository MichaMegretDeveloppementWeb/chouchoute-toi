{{--
    Publie depuis falcon/ui-kit pour porter la hauteur tactile.

    Seul `$sizes` change : sous 640 les deux tailles du kit sortent a 32 px de
    haut, en dessous de ce qu'un doigt atteint sans se reprendre. Le reste est
    celui du kit, inchange.
--}}
@props([
    'variant' => 'primary',
    'size' => 'default',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    // On a link: the HTML target. On a button: the wire:target of the loading state.
    'target' => null,
])

@php
$base = 'inline-flex items-center gap-x-1.5 rounded-lg text-[13px] font-medium transition-colors';

$variants = [
    'primary' => 'bg-gray-900 text-white shadow-sm hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200',
    'secondary' => 'bg-white text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700',
    'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-500 dark:bg-red-600 dark:hover:bg-red-500',
    'ghost' => 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
];

/*
 | `max-sm:min-h-11` : sous 640 un bouton se vise au doigt, et les deux tailles
 | du kit sortent a 32 px de haut. La mesure est posee ici plutot qu'a chaque
 | appel, parce que rien dans le rendu ne donne de prise a une feuille : les
 | classes sont celles d'un bouton quelconque.
 |
 | Un minimum et non une hauteur : un bouton dont le libelle passe a la ligne
 | doit pouvoir grandir.
 */
$sizes = [
    'default' => 'px-4 py-1.5 max-sm:min-h-11',
    'compact' => 'px-3 py-1.5 max-sm:min-h-11',
];

$classes = implode(' ', [
    $base,
    $variants[$variant] ?? $variants['primary'],
    $sizes[$size] ?? $sizes['default'],
    $disabled ? 'opacity-50 cursor-not-allowed' : '',
    $loading ? 'disabled:opacity-75 disabled:cursor-not-allowed' : '',
]);

/*
 | On a link, `target` is the HTML attribute — that is what the word means there, and
 | swallowing it silently kept `target="_blank"` from ever opening a new tab. It stays the
 | Livewire loading target on a real <button>, where the HTML attribute has no meaning.
 */
$isLink = $href && !$disabled;
$wireTarget = ($target && !$isLink) ? "wire:target=\"{$target}\"" : '';
@endphp

@if($isLink)
<a href="{{ $href }}"
   @if($target) target="{{ $target }}" @endif
   @if($target === '_blank') rel="noopener" @endif
   {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
<button
    {{ $disabled ? 'disabled' : '' }}
    @if($loading) wire:loading.attr="disabled" {!! $wireTarget !!} @endif
    {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}
>
    @if($loading)
    <svg {!! $wireTarget !!} wire:loading class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span {!! $wireTarget !!} wire:loading.remove class="inline-flex items-center gap-x-1.5">{{ $slot }}</span>
    <span {!! $wireTarget !!} wire:loading>Chargement...</span>
    @else
    {{ $slot }}
    @endif
</button>
@endif
