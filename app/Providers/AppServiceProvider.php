<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * What the ORM lets through silently, everywhere but production.
         *
         * A lazy load, an attribute written that no model accepts, a column
         * read that the query never selected: all three are ordinary code that
         * works, until the volume or the projection changes. They are off in
         * production on purpose — an exception in front of a visitor would be
         * worse than the defect — which is exactly why they must be loud here,
         * where somebody is watching.
         */
        // Par la facade et non `$this->app` : l'analyseur ajoute au contrat de
        // l'application la liste des methodes statiques de la facade, et la
        // question posee sur l'instance se lit alors comme un appel statique.
        Model::shouldBeStrict(! App::isProduction());

        /*
         * Nothing to declare for the kit's components.
         *
         * falcon/ui-kit resolves the stack itself: a package's own skin wins
         * inside that package, an application's override lives in
         * `resources/views/vendor/ui/components/`, and the kit's own comes
         * last. A `UiKit::componentsFor()` call stood here for the version that
         * asked the host to declare it, and that class no longer exists.
         */
    }
}
