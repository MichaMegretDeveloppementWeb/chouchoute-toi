@props([
    'label' => null,
])

@php
$sectionLabel = 'fb-barre-titre whitespace-nowrap max-h-6 mb-2 overflow-hidden transition-[opacity,max-height,margin] duration-300 ease-in-out';
@endphp

<li>
    @if($label)
        <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 {{ $sectionLabel }}">{{ $label }}</div>
    @endif
    <ul class="-mx-2 space-y-0.5">
        {{ $slot }}
    </ul>
</li>
