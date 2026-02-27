@php
    $hasGoogleReviews = ! empty($googleReviews);

    // Fallback : exemples si pas d'avis Google configurés
    $exemples = [
        [
            'nom' => 'Sophie M.',
            'note' => 5,
            'type' => 'Pose complète',
            'ville' => 'Évian-les-Bains',
            'texte' => 'Je cherchais depuis longtemps une technicienne qui se déplace à domicile. Amandine est non seulement très professionnelle, mais elle prend le temps d\'écouter ce que l\'on veut. Mes cils sont magnifiques, naturels et confortables. Je ne peux plus m\'en passer !',
        ],
        [
            'nom' => 'Marie L.',
            'note' => 5,
            'type' => 'Volume russe',
            'ville' => 'Thonon-les-Bains',
            'texte' => 'Résultat bluffant ! Amandine a su me conseiller le volume qui correspondait à la forme de mes yeux. Le fait que ce soit à domicile, c\'est un vrai plus : on est détendue, dans son cocon. Je recommande les yeux fermés (c\'est le cas de le dire !).',
        ],
        [
            'nom' => 'Camille D.',
            'note' => 5,
            'type' => 'Remplissage',
            'ville' => 'Publier',
            'texte' => 'Cela fait maintenant un an que je fais mes remplissages avec Amandine. Toujours ponctuelle, soigneuse et de bons conseils pour l\'entretien entre deux séances. Un vrai moment de détente à chaque fois.',
        ],
        [
            'nom' => 'Julie R.',
            'note' => 5,
            'type' => 'Pose complète',
            'ville' => 'Amphion',
            'texte' => 'Première expérience en extensions de cils et je suis conquise. Amandine m\'a tout expliqué, m\'a rassurée et le résultat est exactement ce que je voulais : un regard plus ouvert, sans maquillage. Merci !',
        ],
        [
            'nom' => 'Léa B.',
            'note' => 5,
            'type' => 'Volume russe',
            'ville' => 'Maxilly',
            'texte' => 'Un vrai moment de bien-être ! Amandine est venue chez moi, tout était impeccable. Le résultat est top : mes cils ont l\'air tellement naturels que tout le monde me demande mon secret. Je recommande à 100%.',
        ],
        [
            'nom' => 'Nathalie P.',
            'note' => 5,
            'type' => 'Remplissage',
            'ville' => 'Neuvecelle',
            'texte' => 'Fidèle depuis bientôt 2 ans. Ce que j\'apprécie le plus c\'est la régularité du travail d\'Amandine : à chaque remplissage, c\'est aussi parfait que la première pose. Et le confort du domicile, c\'est imbattable.',
        ],
    ];

    $temoignages = $hasGoogleReviews ? ($googleReviews ?? []) : $exemples;
@endphp

<section class="mb-[150px] max-md:mb-20 pt-12">
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Témoignages
        </p>

        <div class="mb-16 flex flex-wrap items-end justify-between gap-4 max-md:mb-10">
            <h1 class="text-[38px] font-light leading-[1.5] text-wine max-md:text-[28px]">
                Ce que disent mes clientes
            </h1>

            @if ($hasGoogleReviews && $googleRating)
                <div class="flex items-center gap-3">
                    <div class="flex gap-0.5">
                        @for ($i = 0; $i < round($googleRating); $i++)
                            <x-icon.star-filled class="text-wine" />
                        @endfor
                    </div>
                    <span class="text-sm text-charcoal">
                        {{ number_format($googleRating, 1, ',', '') }}/5 sur Google
                        @if ($googleTotal)
                            ({{ $googleTotal }} avis)
                        @endif
                    </span>
                </div>
            @endif
        </div>

        <div>
            @foreach ($temoignages as $index => $temoignage)
                <div class="border-t border-wine/10" data-accordion-item>
                    <button class="flex w-full items-center gap-4 py-5 text-left" data-accordion-trigger>
                        <div class="accordion-icon text-charcoal" data-accordion-icon>
                            <span class="accordion-icon__h"></span>
                            <span class="accordion-icon__v"></span>
                        </div>

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sand text-sm font-medium text-wine">
                            {{ mb_substr($temoignage['nom'], 0, 1) }}
                        </div>

                        <span class="flex-1 text-base font-normal text-dark">{{ $temoignage['nom'] }}</span>

                        @if ($hasGoogleReviews)
                            <span class="hidden items-center gap-1 text-sm text-charcoal sm:flex">
                                @if (! empty($temoignage['date']))
                                    {{ $temoignage['date'] }}
                                @endif
                            </span>
                        @else
                            <span class="hidden items-center gap-3 text-sm text-charcoal sm:flex">
                                <span>{{ $temoignage['type'] }}</span>
                                <span class="text-charcoal/30">|</span>
                                <span>{{ $temoignage['ville'] }}</span>
                            </span>
                        @endif
                    </button>

                    <div class="accordion-content max-h-0 overflow-hidden transition-all duration-300" data-accordion-content>
                        <div class="pb-2 pl-[88px] pt-4 max-md:pl-0 max-md:pt-3">
                            @if (! $hasGoogleReviews)
                                <div class="mb-3 flex items-center gap-1 sm:hidden">
                                    <span class="text-sm text-charcoal">{{ $temoignage['type'] }}</span>
                                    <span class="text-charcoal/30">|</span>
                                    <span class="text-sm text-charcoal">{{ $temoignage['ville'] }}</span>
                                </div>
                            @endif

                            <div class="mb-3 flex gap-0.5">
                                @for ($i = 0; $i < $temoignage['note']; $i++)
                                    <x-icon.star-filled class="text-wine" />
                                @endfor
                            </div>

                            <p class="max-w-2xl text-base leading-[1.7] text-charcoal">
                                « {{ $temoignage['texte'] }} »
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (! $hasGoogleReviews)
            <p class="mt-8 text-center text-xs text-charcoal/50">
                Témoignages rédigés à titre d'exemple. Ils seront remplacés par de vrais avis Google.
            </p>
        @endif
    </div>
</section>
