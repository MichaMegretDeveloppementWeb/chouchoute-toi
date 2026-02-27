<header
    class="header fixed top-0 left-0 right-0 z-50 transition-all duration-300"
    data-header
>
    <nav class="relative z-10 mx-auto flex max-w-[1336px] items-center justify-between px-5 py-2.5 lg:grid lg:grid-cols-[1fr_auto_1fr]">
        {{-- Navigation gauche (desktop) --}}
        <ul class="hidden items-center gap-5 lg:flex">
            <li>
                <a href="{{ route('home') }}" class="header__link text-sm font-normal uppercase tracking-wide text-black transition-opacity hover:opacity-60 {{ request()->routeIs('home') ? 'border-b border-black pb-0.5' : '' }}">
                    Accueil
                </a>
            </li>
            <li>
                <a href="{{ route('prestations') }}" class="header__link text-sm font-normal uppercase tracking-wide text-black transition-opacity hover:opacity-60 {{ request()->routeIs('prestations') ? 'border-b border-black pb-0.5' : '' }}">
                    Prestations
                </a>
            </li>
            <li>
                <a href="{{ route('about') }}" class="header__link text-sm font-normal uppercase tracking-wide text-black transition-opacity hover:opacity-60 {{ request()->routeIs('about') ? 'border-b border-black pb-0.5' : '' }}">
                    À propos
                </a>
            </li>
        </ul>

        {{-- Logo centre --}}
        <a href="{{ route('home') }}" class="header__logo text-center leading-none">
            <span class="block text-xl font-bold tracking-widest text-black uppercase">Chouchoute-toi</span>
            <span class="block font-signature text-3xl text-wine/80 text-right">By Amande</span>
        </a>

        {{-- Navigation droite + actions (desktop) --}}
        <div class="hidden items-center justify-end gap-5 lg:flex">
            <a href="{{ route('reviews') }}" class="header__link text-sm font-normal uppercase tracking-wide text-black transition-opacity hover:opacity-60 {{ request()->routeIs('reviews') ? 'border-b border-black pb-0.5' : '' }}">
                Avis
            </a>
            <a href="{{ route('contact') }}" class="header__link text-sm font-normal uppercase tracking-wide text-black transition-opacity hover:opacity-60 {{ request()->routeIs('contact') ? 'border-b border-black pb-0.5' : '' }}">
                Contact
            </a>
            <span class="h-4 w-px bg-black/15"></span>
            <a href="tel:+33671637666" class="text-sm text-black transition-opacity hover:opacity-60">
                <x-icon.phone class="h-5 w-5" />
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-[10px] bg-black px-5 py-2.5 text-sm text-white transition-colors hover:bg-dark">
                Réserver
                <x-icon.arrow-top-right width="12" height="12" />
            </a>
        </div>

        {{-- Hamburger mobile --}}
        <button
            class="header__hamburger flex flex-col gap-1.5 lg:hidden"
            aria-label="Ouvrir le menu"
            data-menu-toggle
        >
            <span class="block h-0.5 w-6 bg-black transition-transform" data-line-1></span>
            <span class="block h-0.5 w-6 bg-black transition-opacity" data-line-2></span>
            <span class="block h-0.5 w-6 bg-black transition-transform" data-line-3></span>
        </button>
    </nav>

    {{-- Menu mobile --}}
    <div
        class="header__mobile-menu fixed inset-0 bg-cream lg:hidden"
        data-mobile-menu
    >
        <div class="flex h-full flex-col items-center justify-center gap-8">
            <a href="{{ route('home') }}" class="text-lg font-normal uppercase tracking-wide text-black">Accueil</a>
            <a href="{{ route('prestations') }}" class="text-lg font-normal uppercase tracking-wide text-black">Prestations</a>
            <a href="{{ route('about') }}" class="text-lg font-normal uppercase tracking-wide text-black">À propos</a>
            <a href="{{ route('reviews') }}" class="text-lg font-normal uppercase tracking-wide text-black">Avis</a>
            <a href="{{ route('contact') }}" class="text-lg font-normal uppercase tracking-wide text-black">Contact</a>
            <a href="{{ route('contact') }}" class="mt-4 inline-flex items-center gap-2 rounded-[10px] bg-black px-6 py-3 text-sm text-white">
                Réserver
                <x-icon.arrow-top-right width="12" height="12" />
            </a>
        </div>
    </div>
</header>

{{-- Spacer pour compenser le header fixed --}}
<div class="h-19 lg:h-[80px]"></div>
