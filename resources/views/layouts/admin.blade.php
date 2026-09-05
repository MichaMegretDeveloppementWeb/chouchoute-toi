{{--
    Livewire layout adapter for the falcon/analytics screens
    (config/analytics.php · dashboard.layout / marketing.layout).

    The chrome itself lives in the <x-layout.admin> component so the package
    pages and the application's own back-office views share one single shell.
--}}
{{-- `charts` · analytics appelle `new window.Chart(...)` lui-même, et n'a donc
     personne pour charger la bibliothèque. Voir la prop dans le composant. --}}
<x-layout.admin :title="$title ?? 'Analytics'" :charts="true">
    {{ $slot }}
</x-layout.admin>
