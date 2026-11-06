<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Account;

use App\Booking\Accounts;
use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Account\OwnerLockService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Data\Account\AccountData;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * « At least one owner » is held by the rows of the owners · every write that
 * could leave none locks them first and decides on what it reads under the
 * lock. Outside the transaction a test holds, so that a second connection sees
 * what this one writes, and the rows it lays are removed after it.
 */
final class TheOwnersAreHeldWhileAnAccountChangesTest extends TestCase
{
    /** @var list<int> */
    private array $laid = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! RefreshDatabaseState::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            RefreshDatabaseState::$migrated = true;
        }

        config(['database.connections.other' => config('database.connections.'.config('database.default'))]);
    }

    protected function tearDown(): void
    {
        DB::connection('other')->disconnect();
        Admin::query()->whereKey($this->laid)->delete();

        parent::tearDown();
    }

    /** @return array<string, array{callable(Accounts, int): mixed}> */
    public static function provideTheWrites(): array
    {
        return [
            'a new access' => [static fn (Accounts $accounts, int $account): int => $accounts->create(new AccountData('Louise Bernard', 'louise@exemple.fr', role: 'collaborator'))],
            'a fiche saved again' => [static fn (Accounts $accounts, int $account) => $accounts->update($account, new AccountData('Julie Martin', 'julie@exemple.fr', role: 'collaborator'))],
            'a fiche archived' => [static fn (Accounts $accounts, int $account) => $accounts->suspended($account, true)],
            'a fiche removed' => [static fn (Accounts $accounts, int $account) => $accounts->removed($account)],
        ];
    }

    /** It is the lock of an owner that makes each write wait, and nothing else. */
    #[DataProvider('provideTheWrites')]
    public function test_each_write_waits_for_whoever_holds_an_owner(callable $write): void
    {
        $owner = $this->anAccount(Role::Owner, 'amandine@exemple.fr');
        $collaborator = $this->anAccount(Role::Collaborator, 'julie@exemple.fr');

        $other = DB::connection('other');
        $other->beginTransaction();
        $other->select('select id from admins where id = ? for update', [$owner->id]);
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            DB::transaction(static fn () => $write(app(Accounts::class), $collaborator->id));
            $this->fail('The write did not wait for the owner held elsewhere.');
        } catch (Throwable $waited) {
            $query = $this->queryIn($waited);

            $this->assertNotNull($query, 'The write failed for another reason · '.$waited->getMessage());
            $this->assertStringContainsString('for update', $query->getSql());
            $this->assertStringContainsString('`admins`', $query->getSql());
            $this->assertSame([$owner->id], $query->getBindings());
        } finally {
            $other->rollBack();
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
            Admin::query()->where('email', 'louise@exemple.fr')->delete();
        }

        $this->assertSame(Role::Collaborator, $collaborator->fresh()?->role);
        $this->assertNull($collaborator->fresh()->suspended_at);
    }

    /** Two owners when the fiche asked · one when it saves · the last one keeps the role. */
    public function test_the_last_owner_is_refused_under_the_lock(): void
    {
        $amandine = $this->anAccount(Role::Owner, 'amandine@exemple.fr');
        $camille = $this->anAccount(Role::Owner, 'camille@exemple.fr');
        $demoted = new AccountData('Amandine', 'amandine@exemple.fr', role: 'collaborator');

        $this->assertNull(app(Accounts::class)->refusalFor($amandine->id, $demoted), 'Two owners, so the fiche may ask.');

        $camille->forceFill(['suspended_at' => CarbonImmutable::now()])->save();

        try {
            DB::transaction(static fn () => app(Accounts::class)->update($amandine->id, $demoted));
            $this->fail('The last owner lost the role.');
        } catch (RuntimeException $refused) {
            $this->assertStringContainsString('is the last owner in service', $refused->getMessage());
        }

        $this->assertSame(Role::Owner, $amandine->fresh()?->role);
    }

    /** Archived or removed while the other owner leaves · the last one stays, open. */
    public function test_the_last_owner_is_never_cut_off_once_the_other_left(): void
    {
        $amandine = $this->anAccount(Role::Owner, 'amandine@exemple.fr');
        $camille = $this->anAccount(Role::Owner, 'camille@exemple.fr');
        $camille->forceFill(['suspended_at' => CarbonImmutable::now()])->save();

        DB::transaction(static fn () => app(Accounts::class)->suspended($amandine->id, true));
        DB::transaction(static fn () => app(Accounts::class)->removed($amandine->id));

        $this->assertNull($amandine->fresh()?->suspended_at);
    }

    public function test_the_owners_are_held_inside_a_transaction_only(): void
    {
        $this->expectException(LogicException::class);

        app(OwnerLockService::class)->holdTheOwners();
    }

    private function anAccount(Role $role, string $email): Admin
    {
        $account = Admin::factory()->create(['role' => $role, 'email' => $email]);
        $this->laid[] = $account->id;

        return $account;
    }

    private function queryIn(Throwable $failure): ?QueryException
    {
        for ($cause = $failure; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause instanceof QueryException) {
                return $cause;
            }
        }

        return null;
    }
}
