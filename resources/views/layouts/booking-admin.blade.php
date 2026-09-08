{{--
    The bridge between the back-office and falcon/booking's screens.

    The site's shell is a Blade component (`<x-layout.admin>`), while the
    package expects a view extensible by `@extends`. This view joins the two:
    it is named in `booking.admin.layout`, and renders the package's section
    into the component's slot.

    The intended consequence: the agenda's screens inherit the sidebar, the user
    menu, dark mode and the back-office's assets instead of living inside the
    package's own shell.
--}}
{{-- The title comes from the screen and not from the family of pages: the
     topbar says « Planning » or « Prestations », not « Agenda » for all four.
     The fallback covers a package screen that supplies none. --}}
<x-layout.admin :title="$bookingTitle ?? 'Agenda'" :wide="$bookingWide ?? false">
    @yield('content')
</x-layout.admin>
