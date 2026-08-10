{{--
    Livewire layout adapter for the falcon/analytics screens
    (config/analytics.php · dashboard.layout / marketing.layout).

    The chrome itself lives in the <x-layout.admin> component so the package
    pages and the application's own back-office views share one single shell.
--}}
<x-layout.admin :title="$title ?? 'Analytics'">
    {{ $slot }}
</x-layout.admin>
