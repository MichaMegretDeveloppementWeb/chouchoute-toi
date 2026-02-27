@php
    $questions = [
        [
            'question' => 'Combien de temps dure une pose complète ?',
            'reponse' => 'Une pose complète dure en moyenne 1h30 à 2h30 selon le résultat souhaité. Vous serez confortablement installée chez vous pendant toute la durée de la prestation.',
        ],
        [
            'question' => 'Les extensions abîment-elles les cils naturels ?',
            'reponse' => 'Non, à condition qu\'elles soient posées par une professionnelle. J\'utilise des produits hypoallergéniques et une technique respectueuse du cycle naturel de vos cils.',
        ],
        [
            'question' => 'À quelle fréquence faut-il faire un remplissage ?',
            'reponse' => 'Pour maintenir un regard parfait, je recommande un remplissage toutes les 2 à 3 semaines selon votre cycle de renouvellement capillaire.',
        ],
        [
            'question' => 'Comment préparer mon rendez-vous ?',
            'reponse' => 'Venez démaquillée au niveau des yeux et évitez les crèmes huileuses le jour même. Prévoyez un espace confortable pour vous allonger. Je m\'occupe de tout le reste !',
        ],
    ];
@endphp

<section class="pb-[150px] max-md:pb-20" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            FAQ
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Questions fréquentes
        </h2>

        <div>
            @foreach ($questions as $faq)
                <div class="border-t border-wine/10" data-home-faq-item>
                    <button class="flex w-full items-center gap-4 py-5 text-left" data-home-faq-trigger>
                        <div class="accordion-icon text-charcoal" data-home-faq-icon>
                            <span class="accordion-icon__h"></span>
                            <span class="accordion-icon__v"></span>
                        </div>
                        <span class="flex-1 text-base font-medium text-dark">{{ $faq['question'] }}</span>
                    </button>

                    <div class="max-h-0 overflow-hidden transition-all duration-300" data-home-faq-content>
                        <p class="pb-2 pl-12 pt-4 text-base leading-[1.7] text-charcoal max-md:pl-0 max-md:pt-3">
                            {{ $faq['reponse'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('contact') }}#faq" class="inline-flex items-center gap-2 text-sm text-dark transition-all duration-300 hover:underline">
                Voir toutes les questions
                <x-icon.arrow-top-right />
            </a>
        </div>
    </div>
</section>
