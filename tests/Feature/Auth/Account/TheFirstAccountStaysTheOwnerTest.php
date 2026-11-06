<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Account;

use App\Enums\Account\Role;
use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The original account is the owner, and every account written since names
 * its role · the base refuses one that does not, the model offering none.
 */
final class TheFirstAccountStaysTheOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_original_account_is_written_as_the_owner(): void
    {
        $this->seed(AdminSeeder::class);

        $this->assertSame(Role::Owner, Admin::query()->where('email', 'amandine@chouchoute-toi.com')->sole()->role);
    }

    public function test_a_row_written_without_a_role_is_refused(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('role');

        DB::table('admins')->insert([
            'name' => 'Amandine',
            'email' => 'amandine@exemple.fr',
            'password' => bin2hex(random_bytes(8)),
        ]);
    }

    public function test_an_account_written_without_a_role_is_refused(): void
    {
        $this->expectException(QueryException::class);

        (new Admin)->forceFill([
            'name' => 'Julie',
            'email' => 'julie@exemple.fr',
            'password' => bin2hex(random_bytes(8)),
        ])->save();
    }

    public function test_the_schema_refuses_a_role_nobody_knows(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('admins_role_chk');

        DB::table('admins')->insert([
            'name' => 'Amandine',
            'email' => 'amandine@exemple.fr',
            'password' => bin2hex(random_bytes(8)),
            'role' => 'superuser',
        ]);
    }

    public function test_a_role_cannot_be_filled_from_a_form(): void
    {
        $admin = new Admin;

        $admin->fill(['name' => 'Julie']);

        $this->assertNotContains('role', $admin->getFillable());
        $this->assertNotContains('suspended_at', $admin->getFillable());
    }
}
