@php
    $questions = [
        [
            'question' => 'Combien de temps dure une pose complète ?',
            'reponse' => 'Une pose complète dure en moyenne 1h30 à 2h30 selon le résultat souhaité (naturel ou volume). Prévoyez un moment de détente : vous serez confortablement installée, les yeux fermés, pendant toute la durée de la prestation.',
        ],
        [
            'question' => 'Les extensions abîment-elles les cils naturels ?',
            'reponse' => 'Non, à condition qu\'elles soient posées par une professionnelle qualifiée. J\'utilise des produits hypoallergéniques et une technique respectueuse du cycle naturel de vos cils. Chaque extension est posée individuellement sur un cil naturel, sans contact avec la paupière.',
        ],
        [
            'question' => 'À quelle fréquence faut-il faire un remplissage ?',
            'reponse' => 'Pour maintenir un regard parfait, je recommande un remplissage toutes les 2 à 3 semaines. Ce délai dépend de votre cycle de renouvellement capillaire naturel et de votre routine quotidienne.',
        ],
        [
            'question' => 'Comment préparer mon rendez-vous ?',
            'reponse' => 'Venez (ou plutôt restez !) démaquillée au niveau des yeux. Évitez les crèmes huileuses sur le contour de l\'œil le jour même. Prévoyez un espace confortable où vous pourrez vous allonger (canapé, lit). Je m\'occupe de tout le reste !',
        ],
        [
            'question' => 'Peut-on se maquiller avec des extensions ?',
            'reponse' => 'Oui, mais avec quelques précautions. Évitez le mascara waterproof et les démaquillants huileux. Je vous donnerai tous les conseils d\'entretien lors de votre première séance pour profiter de vos extensions le plus longtemps possible.',
        ],
        [
            'question' => 'Quelles sont vos disponibilités ?',
            'reponse' => 'Je travaille du lundi au samedi, sur rendez-vous. Je propose des créneaux en journée et en soirée pour m\'adapter à votre emploi du temps. Contactez-moi pour connaître mes prochaines disponibilités.',
        ],
    ];
@endphp

<section id="faq" class="bg-sand py-20 max-md:py-14">
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Questions fréquentes
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Tout savoir sur les extensions de cils
        </h2>

        <div>
            @foreach ($questions as $faq)
                <div class="border-t border-wine/10" data-faq-item>
                    <button class="flex w-full items-center gap-4 py-5 text-left" data-faq-trigger>
                        <div class="accordion-icon text-charcoal" data-faq-icon>
                            <span class="accordion-icon__h"></span>
                            <span class="accordion-icon__v"></span>
                        </div>
                        <span class="flex-1 text-base font-medium text-dark">{{ $faq['question'] }}</span>
                    </button>

                    <div class="max-h-0 overflow-hidden transition-all duration-300" data-faq-content>
                        <p class="pb-2 pl-12 pt-4 text-base leading-[1.7] text-charcoal max-md:pl-0 max-md:pt-3">
                            {{ $faq['reponse'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
