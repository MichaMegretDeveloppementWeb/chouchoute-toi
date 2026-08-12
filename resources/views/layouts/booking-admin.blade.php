{{--
    Pont entre le back-office et les écrans de falcon/booking.

    Le shell du site est un composant Blade (`<x-layout.admin>`), alors que le
    package attend une vue extensible par `@extends`. Cette vue fait la
    jonction : elle est nommée dans `booking.admin.layout`, et rend la section
    du package dans le slot du composant.

    Conséquence voulue : les écrans de l'agenda héritent de la barre latérale,
    du menu utilisateur, du thème sombre et des assets du back-office, au lieu
    de vivre dans la coquille autonome du package.
--}}
<x-layout.admin :title="__('booking::admin.title')">
    @yield('content')
</x-layout.admin>
