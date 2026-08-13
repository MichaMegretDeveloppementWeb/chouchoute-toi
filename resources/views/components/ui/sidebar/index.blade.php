@props([
    'brand' => '',
    'brandLogo' => null,
])

{{--
    Copie locale de <x-ui.sidebar> du falcon/ui-kit.
    Seul le bloc de marque change : le kit impose une icone Heroicon dans une
    pastille de couleur, on y met le logo reel de Chouchoute-toi. Le reste du
    fichier est identique a l'original, et les sous-composants (link, group,
    trigger, user) continuent de venir du package.

    Correctif durable a porter dans le kit : un slot `logo` sur <x-ui.sidebar>,
    qui rendrait cette copie inutile.
--}}

@php
$labelClass = 'whitespace-nowrap lg:opacity-0 lg:max-w-0 lg:overflow-hidden lg:group-hover/sidebar:opacity-100 lg:group-hover/sidebar:max-w-[200px] wide:opacity-100 wide:max-w-[200px] transition-[opacity,max-width] duration-300 ease-in-out';
@endphp

<!-- Mobile sidebar overlay -->
<div id="mobile-sidebar-backdrop" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 lg:hidden opacity-0 pointer-events-none dark:bg-black/60" onclick="closeMobileSidebar()"></div>

<!-- Mobile sidebar (< lg: 1024px) — off-canvas, slides in from left -->
<aside id="mobile-sidebar" class="fixed inset-y-0 left-0 z-50 w-[280px] -translate-x-full transform bg-white transition-transform duration-300 ease-in-out lg:hidden dark:bg-gray-900">
    <div class="flex h-full flex-col">
        <!-- Brand + close button -->
        <div class="flex h-14 items-center justify-between border-b border-gray-100 px-5 dark:border-gray-800">
            <div class="flex items-center gap-x-2.5">
                <img
                    src="{{ $brandLogo }}"
                    alt=""
                    class="h-8 w-8 shrink-0 rounded-lg bg-white object-contain ring-1 ring-black/5 dark:ring-white/10"
                />
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
<aside class="group/sidebar hidden lg:block fixed inset-y-0 left-0 z-30 w-[62px] wide:w-[260px] overflow-clip hover:w-[260px] hover:shadow-[8px_0_24px_-12px_rgba(0,0,0,0.12)] dark:hover:shadow-[8px_0_24px_-12px_rgba(0,0,0,0.4)] wide:hover:shadow-none border-r border-gray-200 bg-white transition-[width,box-shadow] duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900">
    <div class="flex h-full w-[260px] flex-col">
        <!-- Brand -->
        <div class="flex h-14 shrink-0 items-center gap-x-2.5 lg:gap-x-0 lg:group-hover/sidebar:gap-x-2.5 wide:gap-x-2.5 border-b border-gray-100 px-5 transition-[gap] duration-300 ease-in-out dark:border-gray-800">
            <img
                src="{{ $brandLogo }}"
                alt=""
                class="h-8 w-8 shrink-0 rounded-lg bg-white object-contain ring-1 ring-black/5 dark:ring-white/10"
            />
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
