@php
    $categories = config('tarifs.categories');
    $depose = config('tarifs.depose');

    $ideals = [
        'naturelle' => 'Un premier essai, un look naturel au quotidien',
        'volume-leger' => 'Celles qui veulent un regard plus ouvert sans excès',
        'volume-mixte' => 'Un look polyvalent, du quotidien aux soirées',
        'volume-intense' => 'Les grandes occasions, un look glamour affirmé',
    ];

    $tenues = [
        'naturelle' => '3 à 4 semaines',
        'volume-leger' => '3 à 4 semaines',
        'volume-mixte' => '3 à 4 semaines',
        'volume-intense' => '3 à 4 semaines',
    ];
@endphp

<section class="mb-[150px] max-md:mb-20 pt-12">
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Mes prestations
        </p>

        <h1 class="mb-20 text-[38px] font-light leading-[1.5] text-wine max-md:mb-12 max-md:text-[28px]">
            Mes prestations
        </h1>

        {{-- Zigzag des catégories --}}
        @foreach ($categories as $slug => $categorie)
            <div id="{{ $slug }}" class="prestation-detail mb-[100px] grid grid-cols-2 items-center gap-16 max-md:mb-16 max-md:grid-cols-1 max-md:gap-8 {{ $loop->even ? 'prestation-detail--reverse' : '' }}" data-animate>
                <div class="prestation-detail__image {{ $loop->even ? 'order-2 max-md:order-1' : '' }}">
                    <div class="aspect-[4/3] w-full rounded-xl bg-sand flex items-center justify-center" aria-hidden="true">
                        <img src="{{ asset('images/prestations/' . $slug . '.webp') }}" alt="Extensions de cils {{ $categorie['nom'] }} - Chouchoute-toi" class="w-full rounded-xl object-cover" width="640" height="480" loading="lazy">
                    </div>
                </div>

                <div class="prestation-detail__content {{ $loop->even ? 'order-1 max-md:order-2' : '' }}">
                    <h2 class="mb-4 text-[32px] font-light leading-[1.4] text-wine max-md:text-[24px]">
                        {{ $categorie['nom'] }}
                    </h2>

                    <p class="mb-8 text-base leading-[1.7] text-charcoal">
                        {{ $categorie['description'] }}
                    </p>

                    <div class="mb-8 flex flex-wrap gap-4">
                        <div class="rounded-lg bg-sand/60 p-4">
                            <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Durée</p>
                            <p class="text-lg font-semibold text-dark">{{ $categorie['pose']['duree'] }}</p>
                        </div>
                        <div class="rounded-lg bg-sand/60 p-4">
                            <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Tenue</p>
                            <p class="text-lg font-semibold text-dark">{{ $tenues[$slug] }}</p>
                        </div>
                    </div>

                    <p class="mb-6 text-sm text-charcoal">
                        <span class="font-medium text-dark">Idéal pour :</span> {{ $ideals[$slug] }}
                    </p>

                    <div class="flex flex-wrap items-center gap-4">
                        <a href="{{ route('contact', ['volume' => $slug]) }}" data-track-event="pricing.cta.click" data-track-section="prestation-detail" data-track-prop-volume="{{ $slug }}" class="inline-flex items-center gap-2 rounded-[10px] bg-black px-[22px] py-3 text-sm text-white transition-colors duration-300 hover:bg-dark">
                            Réserver cette prestation
                            <x-icon.arrow-top-right />
                        </a>
                        <a href="#tarif-{{ $slug }}" class="inline-flex items-center gap-2 text-sm font-medium text-wine transition-opacity hover:opacity-70">
                            Voir les tarifs
                            <x-icon.arrow-top-right />
                        </a>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Dépose --}}
        <div id="depose" class="prestation-detail mb-[100px] grid grid-cols-2 items-center gap-16 max-md:mb-16 max-md:grid-cols-1 max-md:gap-8" data-animate>
            <div class="prestation-detail__image">
                <div class="aspect-[4/3] w-full rounded-xl bg-sand flex items-center justify-center" aria-hidden="true">
                    <img src="{{ asset('images/prestations/depose.webp') }}" alt="Dépose d'extensions de cils - Chouchoute-toi" class="w-full rounded-xl object-cover" width="640" height="480" loading="lazy">
                </div>
            </div>

            <div class="prestation-detail__content">
                <h2 class="mb-4 text-[32px] font-light leading-[1.4] text-wine max-md:text-[24px]">
                    Dépose
                </h2>

                <p class="mb-8 text-base leading-[1.7] text-charcoal">
                    Vous souhaitez retirer vos extensions ? La dépose professionnelle permet de retirer chaque extension
                    en douceur, sans abîmer vos cils naturels. N'essayez jamais de les retirer vous-même : vous risqueriez
                    d'arracher vos cils.
                </p>

                <div class="mb-8 flex flex-wrap gap-4">
                    <div class="rounded-lg bg-sand/60 p-4">
                        <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Durée</p>
                        <p class="text-lg font-semibold text-dark">{{ $depose['duree'] }}</p>
                    </div>
                </div>

                <p class="mb-6 text-sm text-charcoal">
                    <span class="font-medium text-dark">Inclus :</span> produit dissolvant + shampooing pour cils
                </p>

                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('contact', ['volume' => 'depose']) }}" data-track-event="pricing.cta.click" data-track-section="prestation-detail" data-track-prop-volume="depose" class="inline-flex items-center gap-2 rounded-[10px] bg-black px-[22px] py-3 text-sm text-white transition-colors duration-300 hover:bg-dark">
                        Réserver une dépose
                        <x-icon.arrow-top-right />
                    </a>
                    <a href="#tarif-depose" class="inline-flex items-center gap-2 text-sm font-medium text-wine transition-opacity hover:opacity-70">
                        Voir les tarifs
                        <x-icon.arrow-top-right />
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
