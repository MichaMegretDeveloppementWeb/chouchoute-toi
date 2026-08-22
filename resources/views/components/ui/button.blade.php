{{--
    Publie depuis falcon/ui-kit pour porter la hauteur tactile et les couleurs
    de la reservation.

    Deux ecarts avec le kit : `$sizes`, ses deux tailles sortant a 32 px de haut
    sous 640 ; et les variantes `booking` et `confirm-*`. Le reste suit le kit.
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
    'booking' => 'bg-[var(--fb-primary)] text-white shadow-sm hover:bg-[var(--fb-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--fb-primary)]',
    'secondary' => 'bg-white text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700',
    'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-500 dark:bg-red-600 dark:hover:bg-red-500',
    'ghost' => 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
    'confirm-primary' => '!rounded-[4px] bg-[var(--fb-primary)] text-white !shadow-none hover:bg-[var(--fb-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--fb-primary)]',
    'confirm-secondary' => '!rounded-[4px] border border-gray-300 bg-white text-gray-700 !shadow-none hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--fb-primary)] dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800',
    'confirm-danger' => '!rounded-[4px] bg-red-600 text-white !shadow-none hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600',
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
    'confirm' => 'min-h-10 px-4 py-2.5 max-sm:min-h-11',
];

$classes = implode(' ', [
    $base,
    $variants[$variant] ?? $variants['primary'],
    $sizes[$size] ?? $sizes['default'],
    $disabled ? 'opacity-50 cursor-not-allowed' : '',
    $loading ? 'relative disabled:opacity-75 disabled:cursor-not-allowed' : '',
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
    {{-- Le libelle s'efface sur place plutot que d'etre remplace : le bouton
         garde sa largeur, donc rien ne saute autour de lui. --}}
    <span
        {!! $wireTarget !!}
        wire:loading.class.delay.long="opacity-0"
        class="inline-flex items-center gap-x-1.5 transition-opacity"
    >{{ $slot }}</span>

    {{-- Deux pieges, d'ou ces deux `<span>` : un `{!! !!}` dans une balise de
         composant empeche Blade de la reconnaitre, et Livewire ne masque au
         repos que les combinaisons de modificateurs qu'il liste dans sa
         feuille — `loading.flex.delay.long` n'en est pas une. --}}
    <span {!! $wireTarget !!} wire:loading.delay.long class="absolute inset-0">
        <span class="flex h-full w-full items-center justify-center"><x-ui.spinner size="sm" /></span>
    </span>
    @else
    {{ $slot }}
    @endif
</button>
@endif
