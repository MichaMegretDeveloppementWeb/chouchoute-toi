<?php

namespace App\Providers;

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
