@if (! empty($googleReviews))
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

            @if ($googleRating)
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
            @foreach ($googleReviews as $review)
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
        </div>
    </div>
</section>
@endif
