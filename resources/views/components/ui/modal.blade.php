{{--
    Publie depuis falcon/ui-kit pour porter la variante `form`.

    `form` est le formulaire de rendez-vous : un panneau plus large sur fond gris,
    sans cadre ni ombre, avec un en-tete blanc qui porte le titre et deux boutons
    carres (fermer, valider), et un pied gris sous filet ou vivent les gestes.
    Ses mesures sont relevees sur la reference du projet. Les autres variantes
    sont celles du kit, inchangees.

    Deux slots de plus pour `form` : `valider` (le contenu du bouton vert, ou
    rien pour une coche) et `pied` (les gestes de gauche ; le total et les
    actions de droite passent par `footer` comme ailleurs).
--}}
@props([
    'name' => null,               // Unique name, used to open via $dispatch('open-modal', 'name')
    'variant' => 'content',       // content|confirm|large|wide|form
    'title' => null,
    'onValidate' => null,         // form : l'expression Alpine du bouton de validation
    'validateTarget' => null,     // form : la methode Livewire dont on attend la fin
])

@php
$maxWidth = match($variant) {
    'confirm' => 'max-w-sm',
    // A form whose rows carry several fields side by side. Below this it wraps into a
    // column and reads as a list; above it there is more width than the fields can use.
    'large' => 'max-w-5xl',
    // Media-heavy panels (image cropping, side-by-side diffs) need the screen, not a column.
    'wide' => 'max-w-[min(90rem,92vw)]',
    'form' => 'max-w-[1100px]',
    // A list to pick from, over a form: narrower than the form, anchored at the top.
    'picker' => 'max-w-[662px]',
    default => 'max-w-lg',
};

// Le panneau du formulaire se detache par sa couleur, pas par un contour : un
// cadre et une ombre par-dessus un voile sombre dessinent un lisere dur, la ou
// la reference n'a ni l'un ni l'autre.
$panneau = match ($variant) {
    'form' => 'relative flex w-full flex-col overflow-hidden rounded-xl bg-[var(--fb-cellule)] transition duration-200 ease-out dark:bg-gray-950',
    'picker' => 'relative w-full overflow-hidden rounded-md bg-white transition duration-200 ease-out dark:bg-gray-900',
    default => 'relative w-full rounded-xl border border-gray-200 bg-white shadow-xl transition duration-200 ease-out dark:border-gray-700 dark:bg-gray-900 dark:shadow-2xl dark:shadow-black/20',
};
@endphp

{{-- Un seul chemin de sortie, `fermer()`, qui emet `close-modal` : Echap, le
     voile et le bouton faisaient `open = false` sans rien dire, et la pile de
     modales du back-office, qui rend le focus et pose le piege de tabulation,
     n'apprenait jamais la fermeture.

     Echap ne repond que sur la modale du dessus, marquee par cette pile : deux
     modales ouvertes, une confirmation par-dessus un formulaire, se fermaient
     d'un coup. --}}
<div x-data="{
        open: false,
        fermer() {
            if (! this.open) return;
            this.$dispatch('close-modal', '{{ $name }}');
        },
     }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
     x-on:keydown.escape.window="if ($el.hasAttribute('data-fb-modale-dessus') || ! document.querySelector('[data-fb-modale-dessus]')) fermer()"
     x-show="open"
     x-cloak
     {{ $attributes->merge(['class' => 'fixed inset-0 z-50']) }}>

    {{-- Backdrop --}}
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm dark:bg-black/60" @click="fermer()"></div>

    {{-- Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        {{-- The animation runs on classes, not on x-show. The panel is a descendant of an
             element that x-show hides, and an enter transition started while that ancestor is
             still display:none never completes: the panel stayed invisible on the first open
             even though the modal reported itself open. --}}
        <div x-bind:class="open ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
             @click.stop
             class="{{ $panneau }} {{ $maxWidth }}"
             @if($variant === 'form') style="max-height: 90vh" @endif>

            @if($variant === 'picker')
                {{ $slot }}
            @elseif($variant === 'form')
                {{-- En-tete blanc : le titre a gauche, fermer et valider a droite en
                     deux boutons carres de 39 px. Mesures : 52 de haut, retrait
                     6 28 5, titre 15/700 dans l'encre secondaire. --}}
                <div class="flex shrink-0 items-center gap-x-3 bg-white pb-[5px] pl-7 pr-7 pt-1.5 dark:bg-gray-900">
                    @if($title)
                        <h3 class="min-w-0 flex-1 truncate text-[15px] font-bold leading-6 text-[var(--fb-encre-2)]">{{ $title }}</h3>
                    @endif

                    <button type="button" @click="fermer()"
                        class="flex h-[39px] w-[39px] shrink-0 items-center justify-center rounded-md border border-[var(--fb-encre-2)] text-[var(--fb-encre)] transition-colors hover:bg-[var(--fb-cellule)]"
                        aria-label="Fermer">
                        <x-ui.icon name="arrow-left" class="h-5 w-5" />
                    </button>

                    @if($onValidate)
                        <button type="button" x-on:click="{{ $onValidate }}"
                            @if($validateTarget) wire:loading.attr="disabled" wire:target="{{ $validateTarget }}" @endif
                            class="flex h-[39px] w-[39px] shrink-0 items-center justify-center rounded-md bg-gray-900 text-white transition-colors hover:bg-gray-800 disabled:opacity-60 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                            aria-label="Enregistrer">
                            <x-ui.icon name="check" class="h-5 w-5" stroke-width="2.5" />
                        </button>
                    @endif
                </div>

                {{-- Le corps defile seul : l'en-tete et le pied restent en vue. --}}
                <div class="min-h-0 flex-1 overflow-y-auto bg-[#f6f7f8] px-7 py-4 dark:bg-gray-950">
                    {{ $slot }}
                </div>

                @if(isset($footer))
                    {{-- Pied gris sous filet, 72 px : les gestes a gauche, le
                         total et les actions a droite. --}}
                    <div class="flex min-h-[72px] shrink-0 items-center gap-x-4 border-t border-[var(--fb-trait)] bg-[#f6f7f8] py-3 pl-[18px] pr-7 dark:bg-gray-950">
                        {{ $footer }}
                    </div>
                @endif
            @elseif($variant === 'confirm')
                {{-- Confirmation layout: icon + text side by side --}}
                <div class="p-5">
                    <div class="flex items-start gap-x-3">
                        @if(isset($icon))
                            {{ $icon }}
                        @else
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20">
                                <x-ui.icon name="exclamation-triangle" class="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            @if($title)
                                <h3 class="text-[13px] font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                            @endif
                            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400 leading-relaxed">{{ $slot }}</p>
                        </div>
                    </div>
                    @if(isset($actions))
                        <div class="mt-5 flex items-center justify-end gap-x-2">
                            {{ $actions }}
                        </div>
                    @endif
                </div>
            @else
                {{-- Content layout: header / body / footer --}}
                @if($title)
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-[13px] font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                    <button type="button" @click="fermer()" class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                        <x-ui.icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
                @endif
                <div class="px-5 py-4">
                    {{ $slot }}
                </div>
                @if(isset($footer))
                <div class="flex items-center justify-end gap-x-2 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                    {{ $footer }}
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
