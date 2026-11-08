{{-- Testimonials --}}
<section class="mb-[150px] max-md:mb-20 pt-12"
         @if ($reviews === null) data-reviews-source="{{ route('reviews.list') }}" aria-busy="true" @endif>
    <div class="mx-auto max-w-[1336px] px-5">
        <div class="mb-[72px] flex flex-col items-center text-center max-md:mb-12">
            <p class="mb-3 flex items-center justify-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
                <x-icon.section-marker />
                Témoignages
            </p>

            <h1 class="text-[38px] font-light leading-[1.5] text-wine max-md:text-[28px]">
                Ce que disent mes clientes
            </h1>

            @if ($reviews === null)
                <div class="mt-8 flex flex-col items-center gap-5 motion-safe:animate-pulse max-md:mt-6 max-md:gap-4" aria-hidden="true" data-reviews-waiting>
                    <div class="flex h-16 items-center gap-[18px] max-md:h-[52px]">
                        <div class="h-full w-24 rounded-xl bg-sand max-md:w-20"></div>
                        <div class="grid gap-2">
                            <div class="h-4 w-28 rounded-full bg-sand"></div>
                            <div class="h-4 w-36 rounded-full bg-sand"></div>
                        </div>
                    </div>
                    <div class="flex h-8 items-center">
                        <div class="h-4 w-64 rounded-full bg-sand max-md:w-56"></div>
                    </div>
                </div>
            @elseif ($rating)
                <div class="mt-8 flex flex-col items-center gap-5 max-md:mt-6 max-md:gap-4">
                    <p class="flex items-center gap-[18px]">
                        <span class="text-[64px] font-light leading-none text-wine tabular-nums max-md:text-[52px]">{{ number_format($rating, 1, ',', '') }}</span>
                        <span class="grid justify-items-start gap-1.5 text-left">
                            <span class="flex gap-0.5">
                                @for ($i = 0; $i < round($rating); $i++)
                                    <x-icon.star-filled class="text-wine" width="20" height="20" />
                                @endfor
                            </span>
                            @if ($total)
                                <span class="text-[15px] text-charcoal">{{ $total }} avis sur Google</span>
                            @endif
                        </span>
                    </p>

                    @if ($allReviews || $writeAReview)
                        <p class="flex flex-wrap justify-center gap-x-7 max-md:gap-x-5">
                            @if ($allReviews)
                                <a href="{{ $allReviews }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 py-1.5 text-sm text-dark underline decoration-wine/30 underline-offset-4 transition-colors hover:text-wine hover:decoration-wine">
                                    Voir tous les avis
                                    <x-icon.arrow-top-right width="13" height="13" />
                                </a>
                            @endif
                            @if ($writeAReview)
                                <a href="{{ $writeAReview }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 py-1.5 text-sm text-dark underline decoration-wine/30 underline-offset-4 transition-colors hover:text-wine hover:decoration-wine">
                                    Laisser un avis
                                    <x-icon.arrow-top-right width="13" height="13" />
                                </a>
                            @endif
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="mb-5 flex flex-wrap items-baseline gap-x-4 gap-y-1.5">
            <h2 class="text-lg font-normal text-dark">Quelques avis</h2>
            <p class="text-[13px] text-charcoal/75">Les plus pertinents selon Google Maps, sans tri ni filtre de notre part.</p>
        </div>

        <div>
            @if ($reviews === null)
                <p class="sr-only" data-reviews-waiting>Chargement des avis</p>

                @for ($i = 0; $i < $expected; $i++)
                    <div class="border-t border-wine/10 motion-safe:animate-pulse" aria-hidden="true" data-reviews-waiting data-review-placeholder>
                        <div class="flex items-center gap-4 py-5">
                            <div class="h-6 w-6 shrink-0"></div>
                            <div class="h-10 w-10 shrink-0 rounded-full bg-sand"></div>
                            <div class="h-4 w-40 rounded-full bg-sand max-sm:w-32"></div>
                            <div class="ml-auto hidden h-4 w-52 rounded-full bg-sand md:block"></div>
                        </div>
                    </div>
                @endfor

                <p class="border-t border-wine/10 py-5 text-base text-charcoal" hidden data-reviews-failure>
                    Les avis ne se chargent pas pour l’instant.
                </p>
            @else
                @foreach ($reviews as $review)
                    @php($meta = implode(' · ', array_filter([$review['visite'] ? 'Visité en '.$review['visite'] : null, $review['date'] !== $review['visite'] ? $review['date'] : null])))
                    <div class="border-t border-wine/10" data-accordion-item>
                        <button class="flex w-full items-center gap-4 py-5 text-left" aria-expanded="false" data-accordion-trigger>
                            <div class="accordion-icon text-charcoal" data-accordion-icon>
                                <span class="accordion-icon__h"></span>
                                <span class="accordion-icon__v"></span>
                            </div>

                            @if ($review['photo'])
                                <img src="{{ $review['photo'] }}" alt="" width="40" height="40" loading="lazy" referrerpolicy="no-referrer" class="h-10 w-10 shrink-0 rounded-full object-cover">
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sand text-sm font-medium text-wine">
                                    {{ mb_substr($review['nom'], 0, 1) }}
                                </div>
                            @endif

                            <span class="flex-1 text-base font-normal text-dark">{{ $review['nom'] }}</span>

                            @if ($meta !== '')
                                <span class="hidden text-sm text-charcoal md:inline">{{ $meta }}</span>
                            @endif
                        </button>

                        <div class="accordion-content max-h-0 overflow-hidden transition-all duration-300" data-accordion-content>
                            <div class="pb-6 pl-24 pt-1 max-md:pl-0 max-md:pt-0">
                                @if ($meta !== '')
                                    <p class="mb-2 text-sm text-charcoal md:hidden">{{ $meta }}</p>
                                @endif

                                <div class="flex gap-0.5">
                                    @for ($i = 0; $i < $review['note']; $i++)
                                        <x-icon.star-filled class="text-wine" />
                                    @endfor
                                </div>

                                <p class="mt-3 max-w-2xl text-base leading-[1.7] text-charcoal">
                                    « {{ $review['texte'] }} »
                                </p>

                                @if ($review['lien'] || $review['profil'])
                                    <p class="mt-3 flex flex-wrap gap-x-6">
                                        @if ($review['lien'])
                                            <a href="{{ $review['lien'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 py-1.5 text-sm text-dark underline decoration-wine/30 underline-offset-4 transition-colors hover:text-wine hover:decoration-wine">
                                                Voir l’avis sur Google Maps
                                                <x-icon.arrow-top-right width="13" height="13" />
                                            </a>
                                        @endif
                                        @if ($review['profil'])
                                            <a href="{{ $review['profil'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 py-1.5 text-sm text-charcoal underline decoration-wine/30 underline-offset-4 transition-colors hover:text-wine hover:decoration-wine">
                                                Profil de {{ $review['nom'] }}
                                                <x-icon.arrow-top-right width="13" height="13" />
                                            </a>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
