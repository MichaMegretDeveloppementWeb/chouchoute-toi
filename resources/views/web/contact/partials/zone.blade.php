@php
    $communes = [
        'Évian-les-Bains',
        'Thonon-les-Bains',
        'Publier',
        'Amphion',
        'Maxilly',
        'Neuvecelle',
    ];
@endphp

<section class="mb-[150px] max-md:mb-20" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Zone d'intervention
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Je me déplace chez vous
        </h2>

        <div class="grid grid-cols-2 items-center gap-16 max-md:grid-cols-1 max-md:gap-10">
            <div>
                <p class="mb-8 text-base leading-[1.7] text-charcoal">
                    Basée sur le bassin lémanique, je me déplace à votre domicile pour vos séances d'extensions de cils.
                    Profitez d'un moment de beauté et de détente sans quitter le confort de chez vous.
                </p>

                <div class="mb-8 flex flex-wrap gap-3">
                    @foreach ($communes as $commune)
                        <span class="rounded-full bg-sand px-4 py-2 text-sm font-medium text-dark">{{ $commune }}</span>
                    @endforeach
                </div>

                <p class="text-sm text-charcoal/70">
                    Votre commune n'est pas listée ? Contactez-moi pour vérifier si je peux me déplacer chez vous.
                </p>
            </div>

            <div>
                <div class="aspect-[5/3] w-full rounded-xl bg-sand flex items-center justify-center" aria-hidden="true">
                    <img src="{{ asset('images/contact/zone-lac-leman.webp') }}" alt="Zone d'intervention Chouchoute-toi - bassin lémanique, Évian, Thonon" class="w-full rounded-xl object-cover" width="640" height="480" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>
