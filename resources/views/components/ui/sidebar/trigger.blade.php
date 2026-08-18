@props([])

<button type="button" onclick="openMobileSidebar()" {{ $attributes->merge(['class' => 'rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300']) }}>
    <x-ui.icon name="bars-3" class="h-5 w-5" />
</button>
