<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Team;

use App\Enums\Team\AdminRole;
use App\Models\Admin;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The account a row of the table already holds stays the owner once roles
 * exist: no migration fills data, so the column's own default carries it.
 */
final class TheFirstAccountStaysTheOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_row_that_names_no_role_is_the_owner(): void
    {
        $id = DB::table('admins')->insertGetId([
            'name' => 'Amandine',
            'email' => 'amandine@exemple.fr',
            'password' => 'not-a-hash',
        ]);

        $this->assertSame(AdminRole::Owner, Admin::query()->findOrFail($id)->role);
    }

    public function test_the_schema_refuses_a_role_nobody_knows(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('admins_role_chk');

        DB::table('admins')->insert([
            'name' => 'Amandine',
            'email' => 'amandine@exemple.fr',
            'password' => 'not-a-hash',
            'role' => 'superuser',
        ]);
    }

    public function test_a_role_cannot_be_filled_from_a_form(): void
    {
        $admin = new Admin;

        $admin->fill(['name' => 'Julie']);

        $this->assertNotContains('role', $admin->getFillable());
        $this->assertNotContains('practitioner_id', $admin->getFillable());
        $this->assertNotContains('suspended_at', $admin->getFillable());
    }
}
