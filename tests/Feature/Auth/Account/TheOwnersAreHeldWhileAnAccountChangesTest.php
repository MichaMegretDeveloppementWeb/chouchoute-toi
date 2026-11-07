<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Account;

use App\Booking\Accounts;
use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Account\OwnerLockService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Data\Account\AccountData;
use Illuminate\Database\Events\QueryExecuted;
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
            $this->assertTrue(self::locks($query->getSql()), 'The statement that waited is no locking read · '.$query->getSql());
            $this->assertStringContainsString('`admins`', $query->getSql());
            $this->assertSame([Role::Owner->value], $query->getBindings(), 'The statement that waited is not the read of the owners.');
        } finally {
            $other->rollBack();
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
            Admin::query()->where('email', 'louise@exemple.fr')->delete();
        }

        $this->assertSame(Role::Collaborator, $collaborator->fresh()?->role);
        $this->assertNull($collaborator->fresh()->suspended_at);
    }

    /**
     * Booking has read before the site writes, so an ordinary read here sees
     * the base as it was then · each write reads under a lock first.
     */
    #[DataProvider('provideTheWrites')]
    public function test_each_write_reads_under_a_lock_first(callable $write): void
    {
        $this->anAccount(Role::Owner, 'amandine@exemple.fr');
        $collaborator = $this->anAccount(Role::Collaborator, 'julie@exemple.fr');
        $seen = new class
        {
            /** @var list<string> */
            public array $reads = [];
        };

        try {
            DB::transaction(static function () use ($write, $collaborator, $seen): void {
                DB::listen(static function (QueryExecuted $query) use ($seen): void {
                    if (str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                        $seen->reads[] = $query->sql;
                    }
                });

                $write(app(Accounts::class), $collaborator->id);
            });
        } finally {
            Admin::query()->where('email', 'louise@exemple.fr')->delete();
        }

        $this->assertNotSame([], $seen->reads, 'The write read nothing · nothing was proved.');

        $beforeTheLock = [];

        foreach ($seen->reads as $sql) {
            if (self::locks($sql)) {
                break;
            }

            $beforeTheLock[] = $sql;
        }

        $this->assertSame([], $beforeTheLock, 'These reads came before the first lock.');
    }

    /**
     * Another process promotes a second owner and commits while the archiving
     * of the first waits · once it has, the first is no longer the last, and is
     * archived. The transaction has read before, as booking's does.
     */
    public function test_an_owner_promoted_while_the_write_waits_is_counted(): void
    {
        $amandine = $this->anAccount(Role::Owner, 'amandine@exemple.fr');
        $camille = $this->anAccount(Role::Collaborator, 'camille@exemple.fr');
        $witness = sys_get_temp_dir().'/chouchoute-owners-'.bin2hex(random_bytes(6));
        $child = null;
        $startedAt = 0.0;
        $endedAt = 0.0;

        try {
            DB::transaction(function () use ($amandine, $camille, $witness, &$child, &$startedAt, &$endedAt): void {
                Admin::query()->whereKey($camille->id)->value('role');

                $child = $this->startTheChild($witness, $amandine->id, $camille->id);
                $this->waitUntilItHolds($witness);

                $startedAt = microtime(true);
                app(Accounts::class)->suspended($amandine->id, true);
                $endedAt = microtime(true);
            });
        } finally {
            $exitCode = is_resource($child) ? proc_close($child) : null;
        }

        $said = is_file($witness.'.out') ? (string) file_get_contents($witness.'.out') : '';
        $committing = is_file($witness.'.committing') ? (float) file_get_contents($witness.'.committing') : null;
        $committed = is_file($witness.'.committed');

        foreach (['.locked', '.committing', '.committed', '.out'] as $suffix) {
            @unlink($witness.$suffix);
        }

        $this->assertTrue($committing !== null && $committed, "The second process did not commit (exit {$exitCode}): {$said}");
        $this->assertTrue($committing > $startedAt && $committing < $endedAt, 'The archiving did not wait for the other process · nothing was proved.');
        $this->assertSame(Role::Owner, $camille->fresh()?->role);
        $this->assertNotNull($amandine->fresh()?->suspended_at, 'The owner promoted meanwhile was not counted · the first was kept as the last.');
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

    /** Whether this statement reads under a lock, exclusive or shared. */
    private static function locks(string $sql): bool
    {
        return preg_match('/\s(for update|for share|lock in share mode)\s*$/i', $sql) === 1;
    }

    /**
     * Starts the process that holds the first owner and promotes the second,
     * on the same base, by plain PDO.
     *
     * @return resource
     */
    private function startTheChild(string $witness, int $heldOwner, int $promoted)
    {
        /** @var array{host: string, port: string, database: string, username: string, password: string} $connection */
        $connection = config('database.connections.'.config('database.default'));

        $child = proc_open(
            [PHP_BINARY, dirname(__DIR__, 3).'/Fixtures/promote-an-owner-meanwhile.php', (string) $heldOwner, (string) $promoted, $witness],
            [1 => ['file', $witness.'.out', 'w'], 2 => ['file', $witness.'.out', 'a']],
            $pipes,
            null,
            [
                ...getenv(),
                'CHILD_DB_HOST' => $connection['host'],
                'CHILD_DB_PORT' => $connection['port'],
                'CHILD_DB_DATABASE' => $connection['database'],
                'CHILD_DB_USERNAME' => $connection['username'],
                'CHILD_DB_PASSWORD' => $connection['password'],
            ],
        );

        if (! is_resource($child)) {
            throw new RuntimeException('The second process did not start.');
        }

        return $child;
    }

    private function waitUntilItHolds(string $witness): void
    {
        $deadline = microtime(true) + 10;

        while (! is_file($witness.'.locked')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('The second process never held the owner: '.(is_file($witness.'.out') ? file_get_contents($witness.'.out') : ''));
            }

            usleep(20_000);
        }
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
