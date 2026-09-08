{{--
    The bridge between the back-office and falcon/analytics' screens.

    The site's shell is a Blade component (`<x-layout.admin>`), while the
    package expects a view extensible by `@extends`. This view joins the two:
    it is named in `analytics.dashboard.layout` and `analytics.marketing.layout`,
    and renders the package's section into the component's slot.
--}}
<x-layout.admin :title="$analyticsTitle ?? 'Analytics'">
    @yield('content')
</x-layout.admin>
