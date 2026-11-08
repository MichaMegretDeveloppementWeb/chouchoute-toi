{{-- Testimonials --}}
<section class="mb-[150px] max-md:mb-20 pt-12"
         @if ($reviews === null) data-reviews-source="{{ route('reviews.list') }}" aria-busy="true" @endif>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Témoignages
        </p>

        <div class="mb-16 flex flex-wrap items-end justify-between gap-4 max-md:mb-10">
            <h1 class="text-[38px] font-light leading-[1.5] text-wine max-md:text-[28px]">
                Ce que disent mes clientes
            </h1>

            @if ($reviews === null)
                <div class="flex h-5 items-center gap-3 motion-safe:animate-pulse" aria-hidden="true" data-reviews-waiting>
                    <div class="h-4 w-24 rounded-full bg-sand"></div>
                    <div class="h-4 w-36 rounded-full bg-sand"></div>
                </div>
            @elseif ($rating)
                <div class="flex items-center gap-3">
                    <div class="flex gap-0.5">
                        @for ($i = 0; $i < round($rating); $i++)
                            <x-icon.star-filled class="text-wine" />
                        @endfor
                    </div>
                    <span class="text-sm text-charcoal">
                        {{ number_format($rating, 1, ',', '') }}/5 sur Google
                        @if ($total)
                            ({{ $total }} avis)
                        @endif
                    </span>
                </div>
            @endif
        </div>

        <div>
            @if ($reviews === null)
                <p class="sr-only" data-reviews-waiting>Chargement des avis</p>

                @for ($i = 0; $i < 5; $i++)
                    <div class="border-t border-wine/10 motion-safe:animate-pulse" aria-hidden="true" data-reviews-waiting>
                        <div class="flex items-center gap-4 py-5">
                            <div class="h-6 w-6 shrink-0"></div>
                            <div class="h-10 w-10 shrink-0 rounded-full bg-sand"></div>
                            <div class="h-4 w-40 rounded-full bg-sand max-sm:w-32"></div>
                            <div class="ml-auto hidden h-4 w-24 rounded-full bg-sand sm:block"></div>
                        </div>
                    </div>
                @endfor

                <p class="border-t border-wine/10 py-5 text-base text-charcoal" hidden data-reviews-failure>
                    Les avis ne se chargent pas pour l’instant.
                </p>
            @else
                @foreach ($reviews as $review)
                    <div class="border-t border-wine/10" data-accordion-item>
                        <button class="flex w-full items-center gap-4 py-5 text-left" data-accordion-trigger>
                            <div class="accordion-icon text-charcoal" data-accordion-icon>
                                <span class="accordion-icon__h"></span>
                                <span class="accordion-icon__v"></span>
                            </div>

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sand text-sm font-medium text-wine">
                                {{ mb_substr($review['nom'], 0, 1) }}
                            </div>

                            <span class="flex-1 text-base font-normal text-dark">{{ $review['nom'] }}</span>

                            <span class="hidden items-center gap-1 text-sm text-charcoal sm:flex">
                                @if (! empty($review['date']))
                                    {{ $review['date'] }}
                                @endif
                            </span>
                        </button>

                        <div class="accordion-content max-h-0 overflow-hidden transition-all duration-300" data-accordion-content>
                            <div class="pb-2 pl-[88px] pt-4 max-md:pl-0 max-md:pt-3">
                                <div class="mb-3 flex gap-0.5">
                                    @for ($i = 0; $i < $review['note']; $i++)
                                        <x-icon.star-filled class="text-wine" />
                                    @endfor
                                </div>

                                <p class="max-w-2xl text-base leading-[1.7] text-charcoal">
                                    « {{ $review['texte'] }} »
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
