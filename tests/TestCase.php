<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** The only database the suite may migrate from scratch. */
    public const string DATABASE = 'chouchoute_toi_test';

    /**
     * The application, refused before any test touches it unless it points at
     * the test database · a cached configuration would otherwise hand the
     * suite the working one, which it migrates from scratch.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $config = $app->make('config');

        self::refuseUnlessTheTestDatabase($config->get('database.connections.'.$config->get('database.default').'.database'));

        return $app;
    }

    /** @throws RuntimeException */
    public static function refuseUnlessTheTestDatabase(mixed $database): void
    {
        if ($database !== self::DATABASE) {
            throw new RuntimeException(sprintf(
                "The suite runs on '%s', not on '%s'. Refused: it migrates from scratch and would erase whatever it finds.",
                self::DATABASE,
                is_scalar($database) ? (string) $database : get_debug_type($database),
            ));
        }
    }
}
