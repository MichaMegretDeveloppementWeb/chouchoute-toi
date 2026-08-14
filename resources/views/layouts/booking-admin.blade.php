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
{{-- Le titre vient de l'écran, pas de la famille de pages : la barre du haut
     annonce « Planning » ou « Prestations », et non « Agenda » pour les quatre.
     Le repli couvre un écran du package qui n'en fournirait pas. --}}
<x-layout.admin :title="$bookingTitle ?? 'Agenda'" :wide="$bookingWide ?? false">
    @yield('content')
</x-layout.admin>
