<section class="hero">
    <div class="hero__image bg-sand" aria-hidden="true">
        <img src="{{ asset('images/home/hero.webp') }}" alt="Extensions de cils naturelles - Chouchoute toi, pose à domicile Évian Thonon" class="hero__image" width="1920" height="1080" loading="eager">
    </div>

    <div class="hero__overlay"></div>

    <div class="hero__container">

        <div class="hero__content px-8 md:px-16">
            <h1 class="hero__title">Sublimez votre regard, cil après cil.</h1>

            <p class="hero__subtitle">
                Extensions de cils à domicile sur le bassin lémanique, Évian, Thonon et alentours.
            </p>

            <a href="{{ route('contact') }}" class="hero__cta">
                Réservez votre pose
                <x-icon.arrow-top-right width="16" height="16" />
            </a>
        </div>

        <div class="hero__card">
            <p class="hero__card-title">Pose complète</p>
            <p class="hero__card-detail">À partir de 2h de soin</p>
            <a href="{{ route('prestations') }}" class="hero__card-link">
                Découvrir
                <x-icon.arrow-top-right />
            </a>
        </div>

    </div>

</section>
