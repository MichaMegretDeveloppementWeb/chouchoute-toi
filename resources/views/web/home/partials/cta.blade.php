<section class="cta-banner" data-animate>
    <div class="cta-banner__content mx-auto max-w-[1336px] px-5">
        <h2 class="cta-banner__title">
            Envie d'un regard qui vous ressemble ?
        </h2>

        <p class="cta-banner__text">
            Prenez rendez-vous pour une consultation gratuite. Je me déplace directement chez vous,
            partout sur le bassin lémanique.
        </p>

        <a href="{{ route('contact') }}" data-track-event="contact.cta.click" data-track-section="banniere-accueil" class="cta-banner__cta">
            Prendre rendez-vous
            <x-icon.arrow-top-right />
        </a>

        <div class="cta-banner__features">
            <div class="cta-banner__feature">
                <div class="cta-banner__feature-icon">
                    <x-icon.home />
                </div>
                <span class="cta-banner__feature-label">À domicile</span>
            </div>

            <div class="cta-banner__feature-separator"></div>

            <div class="cta-banner__feature">
                <div class="cta-banner__feature-icon">
                    <x-icon.star />
                </div>
                <span class="cta-banner__feature-label">Qualité premium</span>
            </div>

            <div class="cta-banner__feature-separator"></div>

            <div class="cta-banner__feature">
                <div class="cta-banner__feature-icon">
                    <x-icon.sparkles />
                </div>
                <span class="cta-banner__feature-label">Sur-mesure</span>
            </div>
        </div>
    </div>
</section>
