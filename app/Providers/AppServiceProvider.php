<?php

namespace App\Providers;

use Falcon\UiKit\UiKit;
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
         * Notre pile de composants du kit · `<x-app-ui::sidebar>`.
         *
         * **Sans elle, nos surcharges vaudraient pour tout le monde.** Un
         * fichier posé dans `resources/views/components/ui/` remplace le
         * composant partout, y compris dans les écrans de falcon/booking et de
         * falcon/analytics : c'est ainsi qu'analytics a un jour rendu ses
         * trente-neuf boutons avec le dessin de booking.
         *
         * La pile déclarée ici cherche dans trois dossiers, le premier qui a le
         * fichier gagne ·
         *
         *   1. `resources/views/vendor/app/components/ui/`  (inutilisé, l'hôte
         *      étant déjà le premier niveau de sa propre pile)
         *   2. `resources/views/components/ui/`             nos surcharges
         *   3. les composants du kit                        tout le reste
         *
         * Chaque paquet déclare la sienne de la même façon, et personne
         * n'atteint celle d'un autre.
         */
        UiKit::componentsFor('app', resource_path('views'));
    }
}
