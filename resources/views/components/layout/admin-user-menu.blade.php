@props([
    'name' => '',
    'email' => '',
])

@php
    // The kit's folding classes · its stylesheet gives them their value from the
    // state of the bar, so this view knows no width.
    $labelClass = 'ui-sidebar-label';

    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

{{-- The kit's menu, placed from its button · the folded bar would clip a panel as wide as itself. --}}
<x-ui::dropdown class="w-full">
    <x-slot:trigger>
        <button
            type="button"
            :aria-expanded="open"
            aria-haspopup="true"
            class="admin-account ui-sidebar-gap flex w-full items-center px-5 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
        >
            <x-ui::avatar :initials="$initials" size="default" class="shrink-0" />

            <div class="min-w-0 flex-1 text-left {{ $labelClass }}">
                <p class="truncate text-[13px] font-medium text-gray-900 dark:text-gray-100">{{ $name }}</p>
                <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $email }}</p>
            </div>

            <x-ui::icon
                name="chevron-up-down"
                class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 {{ $labelClass }}"
            />
        </button>
    </x-slot:trigger>

    <x-ui::dropdown-item :href="route('admin.profile')" icon="user-circle">
        Mon profil
    </x-ui::dropdown-item>

    <x-ui::dropdown-item :href="route('home')" icon="arrow-top-right-on-square" target="_blank" rel="noopener">
        Voir le site
    </x-ui::dropdown-item>

    <x-ui::dropdown-item separator />

    {{-- Signing out is a POST, so it carries its own form. --}}
    <form method="POST" action="{{ route('auth.logout') }}">
        @csrf
        <button
            type="submit"
            class="flex w-full items-center gap-x-2 px-3 py-2 text-[13px] text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
        >
            <x-ui::icon name="arrow-right-start-on-rectangle" class="h-4 w-4 text-red-400 dark:text-red-500" />
            Déconnexion
        </button>
    </form>
</x-ui::dropdown>
