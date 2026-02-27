<section class="mb-[150px] max-md:mb-20 pt-12">
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Qui suis-je
        </p>

        <h1 class="mb-20 text-[38px] font-light leading-[1.5] text-wine max-md:mb-12 max-md:text-[28px]">
            Mon histoire
        </h1>

        <div class="grid grid-cols-3 items-start gap-16 max-md:grid-cols-1 max-md:gap-10" data-animate>
            {{-- Texte (2/3) --}}
            <div class="col-span-2 max-md:col-span-1">
                <p class="mb-8 text-xl font-light leading-relaxed text-dark max-md:text-lg">
                    Passionnée par la beauté du regard, je mets mon savoir-faire au service de votre confiance en vous,
                    avec des extensions de cils naturelles et sur-mesure, directement chez vous.
                </p>

                <p class="mb-6 text-base leading-[1.7] text-charcoal">
                    Technicienne certifiée en extensions de cils, j'exerce ce métier par passion depuis plusieurs années.
                    Ce qui m'anime ? Voir le sourire de mes clientes quand elles découvrent leur nouveau regard dans le miroir.
                </p>

                <p class="mb-6 text-base leading-[1.7] text-charcoal">
                    J'ai choisi de me spécialiser dans les prestations à domicile pour offrir une expérience différente :
                    pas de stress du déplacement, pas d'attente en salon. Chez vous, dans votre cocon, vous pouvez vous détendre
                    complètement pendant que je prends soin de votre regard.
                </p>

                <p class="text-base leading-[1.7] text-charcoal">
                    Mon approche est simple : prendre le temps de comprendre vos envies pour créer un regard qui vous ressemble.
                    Du plus naturel au plus intense, chaque prestation est personnalisée selon la forme de vos yeux et votre style.
                </p>
            </div>

            {{-- Portrait (1/3) --}}
            <div class="col-span-1">
                <div class="aspect-[4/4] w-full rounded-xl bg-sand flex items-center justify-center" aria-hidden="true">
                    <img src="{{ asset('images/about/portrait-amandine.webp') }}" alt="Amandine, technicienne en extensions de cils - Chouchoute-toi" class="w-full rounded-xl object-cover" width="440" height="580" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>
