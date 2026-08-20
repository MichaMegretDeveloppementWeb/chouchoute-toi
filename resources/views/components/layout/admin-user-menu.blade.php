@props([
    'name' => '',
    'email' => '',
])

@php
    // Same collapse animation as the ui-kit sidebar labels: the text folds away
    // while the rail is narrow, and unfolds on hover or on a wide screen.
    // Le repli passe par la classe, comme dans les composants publies de la
    // barre : c'est la feuille qui decide, et non la largeur de l'ecran.
    $labelClass = 'fb-sidebar-label whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';

    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div
    x-data="{ open: false }"
    @click.away="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        class="fb-sidebar-gap flex w-full items-center gap-x-3 px-5 py-3 transition-[gap,background-color] duration-300 ease-in-out hover:bg-gray-50 lg:px-0 lg:py-3 dark:hover:bg-gray-800"
    >
        <x-ui.avatar :initials="$initials" size="default" class="shrink-0" />

        <div class="min-w-0 flex-1 text-left {{ $labelClass }}">
            <p class="truncate text-[13px] font-medium text-gray-900 dark:text-gray-100">{{ $name }}</p>
            <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $email }}</p>
        </div>

        <x-ui.icon
            name="chevron-up-down"
            class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 {{ $labelClass }}"
        />
    </button>

    {{-- Opens upward: the block sits at the very bottom of the sidebar. --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute bottom-full left-3 right-3 z-30 mb-2 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:shadow-2xl dark:shadow-black/20"
    >
        <x-ui.dropdown-item :href="route('admin.profile')" icon="user-circle">
            Mon profil
        </x-ui.dropdown-item>

        <x-ui.dropdown-item :href="route('home')" icon="arrow-top-right-on-square" target="_blank" rel="noopener">
            Voir le site
        </x-ui.dropdown-item>

        <x-ui.dropdown-item separator />

        {{-- Sign-out is a POST, so it needs its own form rather than a dropdown-item. --}}
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-x-2 px-3 py-2 text-[13px] text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
            >
                <x-ui.icon name="arrow-right-start-on-rectangle" class="h-4 w-4 text-red-400 dark:text-red-500" />
                Déconnexion
            </button>
        </form>
    </div>
</div>
