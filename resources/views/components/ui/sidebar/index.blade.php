{{--
    Publie depuis falcon/ui-kit. Le rail ne se deploie plus au survol : la
    largeur de la barre ne depend que du bouton de la barre du haut, a toutes
    les largeurs d'ecran. Un rail qui s'ouvrait sous la souris deplacait
    l'entree qu'on visait pendant qu'on avancait vers elle.

    L'etat vit sur `<html>` en `data-fb-barre`, pose avant la peinture. Les
    classes ci-dessous decrivent la barre **deployee** ; la feuille de l'hote
    replie chaque piece par sa classe `fb-barre-*` quand l'attribut le demande.
    La barre du telephone garde ses libelles : les regles visent `.fb-barre`,
    qui n'existe que sur le bureau.

    The bar has two roots, one per breakpoint, so unknown attributes are not
    merged onto either: every attribute this component honours is declared here.
    An undeclared one is dropped without a word.

    Three ways to show a mark, in order of precedence: a `logo` slot for full
    control, `brandLogo` for an image, and failing both the icon pill.
--}}
@props([
    'brand' => '',
    'brandIcon' => 'bolt',
    'brandBg' => 'bg-indigo-600',
    'brandLogo' => null,
])

@php
$labelClass = 'fb-barre-libelle whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';
$logoClass = 'h-8 w-8 shrink-0 rounded-lg bg-white object-contain ring-1 ring-black/5 dark:ring-white/10';
@endphp

<!-- Mobile sidebar overlay -->
<div id="mobile-sidebar-backdrop" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 lg:hidden opacity-0 pointer-events-none dark:bg-black/60" onclick="closeMobileSidebar()"></div>

<!-- Mobile sidebar (< lg: 1024px) — off-canvas, slides in from left -->
<aside id="mobile-sidebar" class="fixed inset-y-0 left-0 z-50 w-[280px] -translate-x-full transform bg-white transition-transform duration-300 ease-in-out lg:hidden dark:bg-gray-900">
    <div class="flex h-full flex-col">
        <!-- Brand + close button -->
        <div class="flex h-14 items-center justify-between border-b border-gray-100 px-5 dark:border-gray-800">
            <div class="flex items-center gap-x-2.5">
                @isset($logo)
                    {{ $logo }}
                @elseif($brandLogo)
                    <img src="{{ $brandLogo }}" alt="" class="{{ $logoClass }}">
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $brandBg }}">
                        <x-ui.icon :name="$brandIcon" class="h-4.5 w-4.5 text-white" />
                    </div>
                @endisset
                <span class="text-[15px] font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ $brand }}</span>
            </div>
            <button type="button" onclick="closeMobileSidebar()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                <x-ui.icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>
        <!-- Mobile nav content -->
        <nav class="flex flex-1 flex-col px-5 pb-4 pt-5">
            <ul class="flex flex-1 flex-col gap-y-6">
                {{ $slot }}
                @if(isset($user))
                <li class="-mx-5 mt-auto border-t border-gray-100 dark:border-gray-800">
                    {{ $user }}
                </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>

<!-- Desktop sidebar (>= lg) — fixed, collapsible between lg and wide, always open on wide+ -->
<aside x-data="barreInfobulle()"
    x-on:mouseover="viser($event)"
    x-on:mouseout="quitter($event)"
    class="fb-barre group/sidebar hidden lg:block fixed inset-y-0 left-0 z-30 w-[260px] overflow-clip border-r border-gray-200 bg-white transition-[width] duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900">

    {{-- Une seule infobulle pour tous les liens du rail, posee a cote de celui
         qu'on survole. Teleportee comme le volet d'une section : la barre est
         en `overflow-clip`, et un panneau y serait rogne. --}}
    <template x-teleport="body">
        <span x-show="ouvert" x-cloak x-init="panneau = $el" role="tooltip"
            x-bind:style="`top: ${haut}px; left: ${gauche}px`"
            class="pointer-events-none fixed z-[60] whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[12px] font-medium text-white shadow-lg dark:bg-gray-100 dark:text-gray-900"></span>
    </template>
    <div class="flex h-full w-[260px] flex-col">
        <!-- Brand -->
        <div class="fb-barre-ecart flex h-14 shrink-0 items-center gap-x-2.5 border-b border-gray-100 px-5 transition-[gap] duration-300 ease-in-out dark:border-gray-800">
            @isset($logo)
                {{ $logo }}
            @elseif($brandLogo)
                <img src="{{ $brandLogo }}" alt="" class="{{ $logoClass }}">
            @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $brandBg }}">
                    <x-ui.icon :name="$brandIcon" class="h-4.5 w-4.5 text-white" />
                </div>
            @endisset
            <span class="text-[15px] font-semibold tracking-tight text-gray-900 dark:text-gray-100 {{ $labelClass }}">{{ $brand }}</span>
        </div>
        <!-- Desktop nav content -->
        <div class="flex flex-1 flex-col overflow-y-auto overflow-x-hidden px-5 pb-4 pt-5">
            <nav class="flex flex-1 flex-col">
                <ul class="flex flex-1 flex-col gap-y-6">
                    {{ $slot }}
                    @if(isset($user))
                    <li class="mt-auto border-t border-gray-100 dark:border-gray-800">
                        {{ $user }}
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
</aside>
