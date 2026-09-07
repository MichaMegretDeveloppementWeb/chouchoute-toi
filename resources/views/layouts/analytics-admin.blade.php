{{--
    Pont entre le back-office et les écrans de falcon/analytics.

    Le shell du site est un composant Blade (`<x-layout.admin>`), alors que le
    package attend une vue extensible par `@extends`. Cette vue fait la
    jonction : elle est nommée dans `analytics.dashboard.layout` et
    `analytics.marketing.layout`, et rend la section du package dans le slot du
    composant.

    Elle rendait `{{ $slot }}` jusqu'au 2026-09-07, quand les écrans d'analytics
    étaient des composants Livewire pleine page. Ils passent désormais par un
    contrôleur et une vue mince, comme ceux de booking — d'où `@yield`, et d'où
    la ressemblance avec `booking-admin.blade.php`, qui n'est plus fortuite.
--}}
<x-layout.admin :title="$analyticsTitle ?? 'Analytics'">
    @yield('content')
</x-layout.admin>
