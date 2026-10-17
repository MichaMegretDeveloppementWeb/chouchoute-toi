<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * The suite runs on the production engine, in a database of its own, and
 * refuses to start anywhere else · it migrates from scratch.
 */
final class TheSuiteRunsOnItsOwnDatabaseTest extends TestCase
{
    public function test_the_suite_runs_on_mysql_in_its_own_database(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame(TestCase::DATABASE, DB::connection()->getDatabaseName());
    }

    public function test_the_working_database_is_refused(): void
    {
        $this->expectException(RuntimeException::class);

        TestCase::refuseUnlessTheTestDatabase('chouchoute_toi');
    }

    public function test_an_in_memory_database_is_refused(): void
    {
        $this->expectException(RuntimeException::class);

        TestCase::refuseUnlessTheTestDatabase(':memory:');
    }
}
