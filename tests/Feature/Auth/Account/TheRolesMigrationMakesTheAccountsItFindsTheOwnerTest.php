<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Account;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The accounts the roles migration finds were written before roles existed ·
 * they become the owner.
 *
 * Outside a transaction · a schema statement ends it, so the test replays the
 * migration on the schema itself, then leaves the schema as it found it.
 */
final class TheRolesMigrationMakesTheAccountsItFindsTheOwnerTest extends TestCase
{
    private const string MIGRATION = 'database/migrations/2026_11_05_160000_add_access_to_admins_table.php';

    public function test_an_account_written_before_the_roles_becomes_the_owner(): void
    {
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertSame(0, Artisan::call('migrate:reset', ['--path' => self::MIGRATION, '--force' => true]));

        $email = bin2hex(random_bytes(4)).'@exemple.fr';
        $replayed = false;

        try {
            DB::table('admins')->insert(['name' => 'Amandine', 'email' => $email, 'password' => bin2hex(random_bytes(8))]);

            $this->assertSame(0, Artisan::call('migrate', ['--path' => self::MIGRATION, '--force' => true]));
            $replayed = true;

            $this->assertSame('owner', DB::table('admins')->where('email', $email)->value('role'));
        } finally {
            if ($replayed) {
                DB::table('admins')->where('email', $email)->delete();
            } else {
                Artisan::call('migrate:fresh', ['--force' => true]);
            }
        }
    }
}
