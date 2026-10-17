<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The site runs on Paris time, and the packages of the suite follow it · the
 * hours they show and the days they count are the salon's.
 */
final class TheSiteRunsOnParisTimeTest extends TestCase
{
    public function test_the_application_and_php_run_on_paris_time(): void
    {
        $this->assertSame('Europe/Paris', config('app.timezone'));
        $this->assertSame('Europe/Paris', date_default_timezone_get());
    }
}
