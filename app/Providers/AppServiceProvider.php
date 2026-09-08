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
         * Our own stack of kit components, `<x-app-ui::sidebar>`. Without it
         * our overrides would hold for everybody: a file laid in
         * `resources/views/components/ui/` replaces the component everywhere,
         * the screens of falcon/booking and falcon/analytics included.
         *
         * The stack searches three folders and the first holding the file
         * wins:
         *
         *   1. `resources/views/vendor/app/components/ui/`  (unused, the host
         *      already being the first level of its own stack)
         *   2. `resources/views/components/ui/`             our overrides
         *   3. the kit's components                         everything else
         *
         * Each package declares its own the same way, and none reaches
         * another's.
         */
        UiKit::componentsFor('app', resource_path('views'));
    }
}
