@php
    $slides = [
        ['type' => 'Naturelle', 'slug' => 'naturelle'],
        ['type' => 'Volume Léger', 'slug' => 'volume-leger'],
        ['type' => 'Volume Mixte', 'slug' => 'volume-mixte'],
        ['type' => 'Volume Intense', 'slug' => 'volume-intense'],
    ];
@endphp

<section class="mb-[150px] max-md:mb-20" data-animate>
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Résultats
        </p>

        <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Découvrez la transformation
        </h2>

        <div class="avant-apres" data-avant-apres-slider>
            @foreach ($slides as $index => $slide)
                <div class="avant-apres__slide {{ $index !== 0 ? 'hidden' : '' }}" data-avant-apres-slide>
                    <div class="avant-apres__before">
                        <div class="flex h-full w-full items-center justify-center bg-sand">
                            <img src="{{ asset('images/home/avant-apres/avant-'. $slide['slug'] .'.webp') }}" alt="Avant extensions {{ $slide['type'] }}" width="1280" height="720" loading="lazy">
                        </div>
                    </div>

                    <div class="avant-apres__after">
                        <div class="flex h-full w-full items-center justify-center bg-sand">
                            <img src="{{ asset('images/home/avant-apres/apres-' . $slide['slug'] . '.webp') }}" alt="Après extensions {{ $slide['type'] }}" width="1280" height="720" loading="lazy">
                        </div>
                    </div>

                    <span class="avant-apres__label avant-apres__label--avant">Avant</span>
                    <span class="avant-apres__label avant-apres__label--apres">Après</span>

                    <div class="avant-apres__handle" data-avant-apres-handle>
                        <div class="avant-apres__handle-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M7 12h10"/>
                                <path d="M3 12l4-4v8l-4-4z" fill="currentColor" stroke="none"/>
                                <path d="M21 12l-4-4v8l4-4z" fill="currentColor" stroke="none"/>
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="avant-apres__nav" data-avant-apres-nav>
                <button class="avant-apres__arrow avant-apres__arrow--prev" type="button" aria-label="Prestation précédente" data-avant-apres-prev>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>

                <div class="avant-apres__nav-center">
                    <span class="avant-apres__slide-label" data-avant-apres-label>{{ $slides[0]['type'] }}</span>
                    <div class="avant-apres__dots" data-avant-apres-dots>
                        @foreach ($slides as $index => $slide)
                            <button
                                class="avant-apres__dot {{ $index === 0 ? 'avant-apres__dot--active' : '' }}"
                                type="button"
                                aria-label="Voir {{ $slide['type'] }}"
                                data-slide-index="{{ $index }}"
                            ></button>
                        @endforeach
                    </div>
                </div>

                <button class="avant-apres__arrow avant-apres__arrow--next" type="button" aria-label="Prestation suivante" data-avant-apres-next>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>
