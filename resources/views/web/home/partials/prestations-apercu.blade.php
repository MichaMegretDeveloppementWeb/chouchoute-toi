@php
    $categories = config('tarifs.categories');
@endphp

<section class="mb-[150px] pt-[10em] max-md:mb-20 max-md:pt-14" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Mes prestations
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Des techniques adaptées à chaque regard
        </h2>

        <div class="grid grid-cols-4 gap-5 gap-x-12 max-lg:grid-cols-2 max-sm:grid-cols-1">
            @foreach ($categories as $slug => $categorie)
                <a href="{{ route('prestations') }}#{{ $slug }}" class="prestation-card group">
                    <div class="prestation-card__image bg-sand flex items-center justify-center" aria-hidden="true">
                        <img src="{{ asset('images/home/prestation-' . $slug . '.webp') }}" alt="Extensions de cils {{ $categorie['nom'] }} - Chouchoute-toi" class="prestation-card__image" width="400" height="500" loading="lazy">
                    </div>

                    <div class="prestation-card__label">
                        {{ $categorie['nom'] }} — dès {{ $categorie['pose']['prix'] }}€
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('prestations') }}" class="inline-flex items-center gap-2 text-sm text-dark transition-all duration-300 hover:underline">
                Découvrir toutes mes prestations
                <x-icon.arrow-top-right />
            </a>
        </div>
    </div>
</section>
