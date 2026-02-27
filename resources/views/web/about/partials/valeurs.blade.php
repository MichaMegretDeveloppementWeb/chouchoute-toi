<section class="mb-[150px] max-md:mb-20" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Mes valeurs
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Ce en quoi je crois
        </h2>

        <div class="grid grid-cols-3 gap-8 max-md:grid-cols-1">
            {{-- Bienveillance --}}
            <div class="rounded-xl border border-charcoal/5 p-8 transition-all duration-300 hover:border-charcoal/15 hover:shadow-sm">
                <div class="mb-6 flex h-[58px] w-[58px] items-center justify-center rounded-[10px] bg-sand">
                    <x-icon.heart class="text-wine" />
                </div>
                <h3 class="mb-3 text-lg font-semibold text-dark">Bienveillance</h3>
                <p class="text-sm leading-relaxed text-charcoal">
                    Chaque cliente est accueillie avec écoute et douceur. Pas de jugement, que de la bienveillance.
                </p>
            </div>

            {{-- Excellence --}}
            <div class="rounded-xl border border-charcoal/5 p-8 transition-all duration-300 hover:border-charcoal/15 hover:shadow-sm">
                <div class="mb-6 flex h-[58px] w-[58px] items-center justify-center rounded-[10px] bg-sand">
                    <x-icon.gem class="text-wine" />
                </div>
                <h3 class="mb-3 text-lg font-semibold text-dark">Excellence</h3>
                <p class="text-sm leading-relaxed text-charcoal">
                    Des produits professionnels haut de gamme, une technique maîtrisée, un souci du détail à chaque pose.
                </p>
            </div>

            {{-- Personnalisation --}}
            <div class="rounded-xl border border-charcoal/5 p-8 transition-all duration-300 hover:border-charcoal/15 hover:shadow-sm">
                <div class="mb-6 flex h-[58px] w-[58px] items-center justify-center rounded-[10px] bg-sand">
                    <x-icon.eye class="text-wine" />
                </div>
                <h3 class="mb-3 text-lg font-semibold text-dark">Personnalisation</h3>
                <p class="text-sm leading-relaxed text-charcoal">
                    Aucun regard ne se ressemble. Chaque pose est adaptée à vos yeux, votre style, vos envies.
                </p>
            </div>
        </div>
    </div>
</section>
