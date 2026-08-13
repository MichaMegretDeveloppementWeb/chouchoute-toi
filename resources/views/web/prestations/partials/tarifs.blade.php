@php
    $categories = config('tarifs.categories');
    $depose = config('tarifs.depose');
    $mention = null;
@endphp

<section id="tarifs" class="mb-[150px] bg-sand py-20 max-md:mb-20 max-md:py-14" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Tarifs
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Mes formules
        </h2>

        <div class="grid grid-cols-4 gap-5 max-lg:grid-cols-2 max-sm:grid-cols-1">
            @foreach ($categories as $slug => $categorie)
                <div id="tarif-{{ $slug }}" class="tarif-card rounded-xl bg-cream p-6 transition-shadow duration-300 hover:shadow-md">
                    <h3 class="mb-2 text-lg font-semibold text-dark">{{ $categorie['nom'] }}</h3>

                    <p class="mb-6 text-[32px] font-light text-wine">{{ $categorie['pose']['prix'] }}€</p>

                    <div class="mb-6 border-t border-charcoal/10 pt-4">
                        <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Pose complète</p>
                        <p class="text-sm text-charcoal">Durée : {{ $categorie['pose']['duree'] }}</p>
                    </div>

                    <div class="mb-6 border-t border-charcoal/10 pt-4">
                        <p class="mb-3 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Remplissages</p>
                        @foreach ($categorie['remplissages'] as $remplissage)
                            <div class="mb-2 flex items-center justify-between text-sm text-charcoal">
                                <span>{{ $remplissage['nom'] }}</span>
                                <span class="font-medium text-dark">{{ $remplissage['prix'] }}€</span>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('contact', ['volume' => $slug]) }}" data-track-event="pricing.cta.click" data-track-section="tarifs" data-track-prop-volume="{{ $slug }}" class="mt-auto flex items-center justify-center gap-2 rounded-[10px] bg-black px-[22px] py-3 text-sm text-white transition-colors duration-300 hover:bg-dark">
                        Réserver
                        <x-icon.arrow-top-right />
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Dépose card --}}
        <div id="tarif-depose" class="mt-5 rounded-xl bg-cream p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-dark">Dépose</h3>
                    <p class="text-sm text-charcoal">{{ $depose['description'] }}</p>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="text-[24px] font-light text-wine">{{ $depose['prix'] }}€</p>
                        <p class="text-xs text-charcoal/60">Durée : {{ $depose['duree'] }}</p>
                    </div>
                    <a href="{{ route('contact', ['volume' => 'depose']) }}" data-track-event="pricing.cta.click" data-track-section="tarifs" data-track-prop-volume="depose" class="inline-flex items-center gap-2 rounded-[10px] bg-black px-[22px] py-3 text-sm text-white transition-colors duration-300 hover:bg-dark">
                        Réserver
                        <x-icon.arrow-top-right />
                    </a>
                </div>
            </div>
        </div>

        @if ($mention)
            <p class="mt-6 text-center text-xs text-charcoal/60">{{ $mention }}</p>
        @endif
    </div>
</section>
