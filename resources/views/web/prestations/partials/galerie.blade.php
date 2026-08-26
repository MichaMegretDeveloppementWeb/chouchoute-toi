<section class="pb-[150px] max-md:pb-20" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Galerie
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Exemples de poses
        </h2>

        <div class="galerie-bento grid grid-cols-2 gap-5 max-md:grid-cols-1">
            {{-- Image 1 : grande à gauche --}}
            <div class="galerie-bento__large row-span-2 max-md:row-span-1">
                <div class="flex h-full min-h-[400px] w-full items-center justify-center rounded-xl bg-sand" aria-hidden="true">
                    <img src="{{ asset('images/prestations/galerie-avant-apres.webp') }}" alt="Avant après extensions de cils naturelles - Chouchoute-toi" class="h-full w-full rounded-xl object-cover" width="660" height="660" loading="lazy">
                </div>
            </div>

            {{-- Image 2 : petite droite haut --}}
            <div class="galerie-bento__small">
                <div class="flex h-full min-h-[190px] w-full items-center justify-center rounded-xl bg-sand" aria-hidden="true">
                    <img src="{{ asset('images/prestations/galerie-portrait.webp') }}" alt="Extensions de cils volume russe - Chouchoute-toi" class="h-full w-full rounded-xl object-cover" width="660" height="320" loading="lazy">
                </div>
            </div>

            {{-- Image 3 : petite droite bas --}}
            <div class="galerie-bento__small">
                <div class="flex h-full min-h-[190px] w-full items-center justify-center rounded-xl bg-sand" aria-hidden="true">
                    <img src="{{ asset('images/prestations/galerie-gros-plan.webp') }}" alt="Gros plan extensions de cils posées - Chouchoute-toi" class="h-full w-full rounded-xl object-cover" width="660" height="320" loading="lazy">
                </div>
            </div>
        </div>

        <p class="mt-6 text-center text-sm text-charcoal/60">
            Photos à titre d'illustration. Les résultats peuvent varier selon la morphologie naturelle de vos cils.
        </p>
    </div>
</section>
