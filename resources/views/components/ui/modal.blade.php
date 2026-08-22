{{--
    Published from falcon/ui-kit to carry the `form` and `confirm` variants.

    `form` est le formulaire de rendez-vous : un panneau plus large sur fond gris,
    sans cadre ni ombre, avec un en-tete blanc qui porte le titre et deux boutons
    carres (fermer, valider), et un pied gris sous filet ou vivent les gestes.
    `confirm` follows the reference's compact hierarchy: warning, centred copy,
    then stacked full-width actions. Dedicated button variants keep these
    measurements from changing actions outside confirmation modals.

    Deux slots de plus pour `form` : `valider` (le contenu du bouton vert, ou
    rien pour une coche) et `pied` (les gestes de gauche ; le total et les
    actions de droite passent par `footer` comme ailleurs).

    Deux slots de telephone : `headerAction`, le geste destructeur a droite de
    l'en-tete, et `validateNote`, une ligne centree sous le bouton ancre. Les
    deux ne se rendent que sous 640, ou l'en-tete et la barre du pouce prennent
    la place que le pied tenait au bureau.
--}}
@props([
    'name' => null,               // Unique name, used to open via $dispatch('open-modal', 'name')
    'variant' => 'content',       // content|confirm|large|wide|form|repeat
    'title' => null,
    'onValidate' => null,         // form : l'expression Alpine du bouton de validation
    'validateTarget' => null,     // form : la methode Livewire dont on attend la fin
])

@php
$maxWidth = match($variant) {
    'confirm' => 'max-w-[440px]',
    // A form whose rows carry several fields side by side. Below this it wraps into a
    // column and reads as a list; above it there is more width than the fields can use.
    'large' => 'max-w-5xl',
    // Media-heavy panels (image cropping, side-by-side diffs) need the screen, not a column.
    'wide' => 'max-w-[min(90rem,92vw)]',
    'form' => 'max-w-[1100px]',
    // The recurrence panel measured on Planity: 800 px at desktop width.
    'repeat' => 'max-w-[800px]',
    // A list to pick from, over a form: narrower than the form, anchored at the top.
    'picker' => 'max-w-[662px]',
    default => 'max-w-lg',
};

// Le panneau du formulaire se detache par sa couleur, pas par un contour : un
// cadre et une ombre par-dessus un voile sombre dessinent un lisere dur, la ou
// la reference n'a ni l'un ni l'autre.
$panneau = match ($variant) {
    'form' => 'relative flex w-full flex-col overflow-hidden rounded-xl bg-[var(--fb-cell)] transition duration-200 ease-out dark:bg-gray-950',
    'picker' => 'relative w-full overflow-hidden rounded-md bg-white transition duration-200 ease-out dark:bg-gray-900',
    'repeat' => 'relative flex w-full flex-col overflow-hidden rounded-lg bg-white transition duration-200 ease-out',
    'confirm' => 'relative w-full overflow-hidden rounded-[7px] bg-white transition duration-200 ease-out dark:bg-gray-900',
    default => 'relative w-full rounded-xl border border-gray-200 bg-white shadow-xl transition duration-200 ease-out dark:border-gray-700 dark:bg-gray-900 dark:shadow-2xl dark:shadow-black/20',
};

$voile = $variant === 'repeat'
    ? 'bg-[rgb(52_66_62/0.5)] backdrop-blur-[8px]'
    : 'bg-gray-900/50 backdrop-blur-sm dark:bg-black/60';

$placement = 'items-center justify-center p-4';
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
     x-on:keydown.escape.window="if ($el.hasAttribute('data-fb-topmost-modal') || ! document.querySelector('[data-fb-topmost-modal]')) fermer()"
     x-show="open"
     x-cloak
     {{ $attributes->merge(['class' => 'fixed inset-0 z-50']) }}>

    {{-- Backdrop --}}
    <div x-show="open" x-transition.opacity class="absolute inset-0 {{ $voile }}" @click="fermer()"></div>

    {{-- Panel --}}
    <div class="flex min-h-full {{ $placement }}">
        {{-- The animation runs on classes, not on x-show. The panel is a descendant of an
             element that x-show hides, and an enter transition started while that ancestor is
             still display:none never completes: the panel stayed invisible on the first open
             even though the modal reported itself open. --}}
        {{-- La hauteur maximale est une classe et non un style en ligne : la
             feuille du telephone ouvre ce panneau en plein ecran, et un style
             en ligne ne se laisse pas battre par une feuille. Il restait 91 px
             de page visible sous le formulaire. --}}
        <div x-bind:class="open ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
             @click.stop
             class="{{ $panneau }} {{ $maxWidth }} @if($variant === 'form') sm:max-h-[90vh] @elseif($variant === 'repeat') max-h-[calc(100vh-2rem)] @endif">

            @if($variant === 'picker')
                {{ $slot }}
            @elseif($variant === 'form')
                {{-- En-tete blanc : le titre a gauche, fermer et valider a droite en
                     deux boutons carres de 39 px. Mesures : 52 de haut, retrait
                     6 28 5, titre 15/700 dans l'encre secondaire. --}}
                <div class="fb-modal-header flex shrink-0 items-center gap-x-3 bg-white pb-[5px] pl-7 pr-7 pt-1.5 dark:bg-gray-900">
                    @if($title)
                        <h3 class="min-w-0 flex-1 truncate text-[15px] font-bold leading-6 text-[var(--fb-text-soft)]">{{ $title }}</h3>
                    @endif

                    <button type="button" @click="fermer()"
        {{-- Sur telephone la fleche est nue : un cadre autour d'un retour, sur
             un ecran qui n'a rien d'autre en haut a gauche, ne dit rien de plus
             et pose une boite de plus dans une page qui n'en veut aucune. --}}
                        class="flex h-[39px] w-[39px] shrink-0 items-center justify-center rounded-md border border-[var(--fb-text-soft)] text-[var(--fb-text)] transition-colors hover:bg-[var(--fb-cell)] max-sm:h-11 max-sm:w-11 max-sm:border-0"
                        aria-label="Fermer">
                        <x-ui.icon name="arrow-left" class="h-5 w-5" />
                    </button>

                    {{-- Sur telephone, l'en-tete ne porte que le retour, le titre
                         et le geste destructeur : la validation descend au pouce,
                         dans la barre ancree en bas. --}}
                    @isset($headerAction)
                        <span class="shrink-0 sm:hidden">{{ $headerAction }}</span>
                    @endisset

                    @if($onValidate)
                        {{-- Pas de libelle a effacer sur un bouton carre :
                             l'icone s'estompe et le spinner passe par-dessus.

                             Les directives vont sur un `<span>` autour de
                             l'icone : une condition ecrite dans une balise de
                             composant empeche Blade de la reconnaitre, et elle
                             sort telle quelle dans la page. --}}
                        <button type="button" x-on:click="{{ $onValidate }}"
                            @if($validateTarget) wire:loading.attr="disabled" wire:target="{{ $validateTarget }}" @endif
                            class="fb-primary-action relative hidden h-[39px] w-[39px] shrink-0 items-center justify-center rounded-md bg-gray-900 text-white transition-colors hover:bg-gray-800 disabled:opacity-60 sm:flex dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                            aria-label="Enregistrer">
                            <span class="flex transition-opacity"
                                @if($validateTarget) wire:loading.class.delay.long="opacity-0" wire:target="{{ $validateTarget }}" @endif>
                                <x-ui.icon name="check" class="h-5 w-5" stroke-width="2.5" />
                            </span>

                            @if($validateTarget)
                                <span wire:loading.delay.long wire:target="{{ $validateTarget }}"
                                    class="absolute inset-0">
                                    <span class="flex h-full w-full items-center justify-center">
                                        <x-ui.spinner size="md" />
                                    </span>
                                </span>
                            @endif
                        </button>
                    @endif
                </div>

                {{-- Le corps defile seul : l'en-tete et le pied restent en vue.

                     Le voile est pose autour de lui et non dedans : dans un
                     conteneur qui defile, il partirait avec le contenu et ne
                     couvrirait plus rien passe le premier ecran. --}}
                <div class="relative flex min-h-0 flex-1 flex-col">
                    <div class="fb-modal-body min-h-0 flex-1 overflow-y-auto bg-[#f6f7f8] px-7 py-4 dark:bg-gray-950">
                        {{ $slot }}
                    </div>

                    {{-- L'en-tete et le pied restent decouverts : une reponse qui
                         ne vient pas ne doit pas emprisonner dans la modale. La
                         validation est exclue, son bouton la portant deja. --}}
                    @if($validateTarget)
                        <div wire:loading.delay.long wire:target.except="{{ $validateTarget }}"
                            class="absolute inset-0 z-20 bg-[#f6f7f8]/80 dark:bg-gray-950/80">
                            <div class="flex h-full w-full items-center justify-center">
                                <x-ui.spinner size="xl" class="text-[var(--fb-text-soft)]" />
                            </div>
                        </div>
                    @endif
                </div>

                @if(isset($footer))
                    {{-- Pied gris sous filet, 72 px : les gestes a gauche, le
                         total et les actions a droite. --}}
                    <div class="fb-modal-footer flex min-h-[72px] shrink-0 items-center gap-x-4 border-t border-[var(--fb-border)] bg-[#f6f7f8] py-3 pl-[18px] pr-7 dark:bg-gray-950">
                        {{ $footer }}
                    </div>
                @endif

                @if($onValidate)
                    {{-- La barre du pouce, et le dernier enfant du panneau : les
                         regles du telephone se posent par classe, mais l'ordre
                         reste ce sur quoi un ajout futur se glisserait.

                         Le meme `wire:loading` que le bouton de l'en-tete :
                         l'action n'a pas de garde, et deux appuis rapides
                         enregistreraient deux fois. --}}
                    {{-- Une section blanche a ombre, comme celles du corps :
                         un filet sur du gris la faisait lire comme le bord de la
                         fenetre plutot que comme la derniere chose de la page. --}}
                    <div class="fb-modal-anchored shrink-0 bg-surface px-4 pb-[calc(0.875rem+env(safe-area-inset-bottom))] pt-3.5 shadow-[0_-1px_4px_rgba(0,0,0,0.13)] sm:hidden">
                        {{-- Le kit conserve son contour par defaut ; le package
                             de reservation utilise ce crochet pour en faire son
                             action primaire pleine, accessible au pouce. --}}
                        <button type="button" x-on:click="{{ $onValidate }}"
                            @if($validateTarget) wire:loading.attr="disabled" wire:target="{{ $validateTarget }}" @endif
                            class="fb-primary-mobile-action relative flex h-11 w-full items-center justify-center rounded border border-[var(--fb-text)] text-[15px] font-normal text-[var(--fb-text)] transition-colors active:bg-[var(--fb-cell)] disabled:opacity-40">
                            <span @if($validateTarget) wire:loading.class.delay.long="opacity-0" wire:target="{{ $validateTarget }}" @endif
                                class="transition-opacity">Enregistrer</span>

                            @if($validateTarget)
                                <x-ui.spinner size="md" wire:loading.delay.long wire:target="{{ $validateTarget }}"
                                    class="absolute inset-0 m-auto" />
                            @endif
                        </button>

                        {{-- Le total se lit sous le bouton, centre : c'est la ou
                             la reference le pose sur telephone, et il y ferme la
                             lecture au lieu de flotter dans un coin du pied. --}}
                        @isset($validateNote)
                            <p class="mt-2.5 text-center text-[13px] font-medium text-[var(--fb-text)]">{{ $validateNote }}</p>
                        @endisset
                    </div>
                @endif
            @elseif($variant === 'repeat')
                {{-- Planity recurrence panel: 53 px header, scrolling white body,
                     then a 64 px footer carrying one full-width action. --}}
                <div class="flex h-[53px] shrink-0 items-center justify-between border-b border-[var(--fb-border)] px-6">
                    @if($title)
                        <h3 class="text-[15px] font-semibold leading-6 text-[var(--fb-text)]">{{ $title }}</h3>
                    @endif

                    <button type="button" @click="fermer()"
                        class="flex h-8 w-8 items-center justify-center rounded-md text-[var(--fb-text-soft)] transition-colors hover:bg-[var(--fb-cell)] hover:text-[var(--fb-text)]"
                        aria-label="Fermer">
                        <x-ui.icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6">
                    {{ $slot }}
                </div>

                @if(isset($footer))
                    <div class="flex min-h-16 shrink-0 items-center border-t border-[var(--fb-border)] px-6 py-3">
                        {{ $footer }}
                    </div>
                @endif
            @elseif($variant === 'confirm')
                {{-- A compact, centred warning followed by full-width actions. --}}
                <div class="px-5 pb-5 pt-6 text-center">
                    <div class="flex flex-col items-center">
                        @if(isset($icon))
                            {{ $icon }}
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 dark:bg-red-500/15">
                                <x-ui.icon name="exclamation-triangle" class="h-[18px] w-[18px] text-red-600 dark:text-red-400" />
                            </div>
                        @endif

                        @if($title)
                            <h3 class="mt-3 text-[15px] font-semibold leading-5 text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                        @endif

                        <div class="mt-1.5 text-[13px] leading-5 text-gray-500 dark:text-gray-400">{{ $slot }}</div>
                    </div>

                    @if(isset($actions))
                        <div class="mt-5 flex w-full flex-col gap-y-2 [&>button]:w-full [&>button]:justify-center">
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
