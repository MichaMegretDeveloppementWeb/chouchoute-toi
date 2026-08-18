@props([
    'href' => '#',
    'letter' => '',
    'color' => 'gray',
])

@php
$labelClass = 'whitespace-nowrap lg:opacity-0 lg:max-w-0 lg:overflow-hidden lg:group-hover/sidebar:opacity-100 lg:group-hover/sidebar:max-w-[200px] wide:opacity-100 wide:max-w-[200px] transition-[opacity,max-width] duration-300 ease-in-out';
$colorMap = [
    'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
    'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
    'pink' => 'bg-pink-50 text-pink-600 dark:bg-pink-500/20 dark:text-pink-400',
    'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400',
    'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400',
    'red' => 'bg-red-50 text-red-600 dark:bg-red-500/20 dark:text-red-400',
    'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400',
    'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
];
$badgeClass = $colorMap[$color] ?? $colorMap['gray'];
@endphp

<li>
    <a href="{{ $href }}" class="group flex items-center gap-x-3 rounded-lg px-2.5 py-[7px] text-[13px] font-medium text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md {{ $badgeClass }} text-[10px] font-semibold">{{ $letter }}</span>
        <span class="{{ $labelClass }}">{{ $slot }}</span>
    </a>
</li>
