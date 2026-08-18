@props([
    'name' => '',
    'email' => '',
    'avatar' => '',
    'href' => '#',
])

@php
$labelClass = 'fb-barre-libelle whitespace-nowrap max-w-[200px] overflow-hidden transition-[opacity,max-width] duration-300 ease-in-out';
@endphp

<a href="{{ $href }}" class="flex items-center fb-barre-ecart gap-x-3 px-5 py-3 hover:bg-gray-50 lg:px-0 lg:py-3 transition-[gap,background-color] duration-300 ease-in-out dark:hover:bg-gray-800">
    <x-ui.avatar :src="$avatar" size="default" class="shrink-0" />
    <div class="min-w-0 flex-1 {{ $labelClass }}">
        <p class="text-[13px] font-medium text-gray-900 dark:text-gray-100 truncate">{{ $name }}</p>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $email }}</p>
    </div>
    <x-ui.icon name="ellipsis-horizontal-circle" class="h-4 w-4 text-gray-400 dark:text-gray-500 {{ $labelClass }}" />
</a>
