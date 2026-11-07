<?php

declare(strict_types=1);

namespace App\Services\Auth\Account;

use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The accounts of the back office as the team's fiches write them · inside the
 * transaction that saves the fiche, the owners held first, and every decision
 * taken on what is read under that lock.
 */
final readonly class AccountWriteService
{
    public function __construct(
        private OwnerLockService $owners,
        private PasswordLinksService $links,
        private AccountStandingService $standing,
    ) {}

    /**
     * The account at this address, taken up with its name and its password, or
     * created under the name given, with no password anyone knows · its number.
     *
     * @throws RuntimeException when the role would leave no owner in service
     */
    public function createOrTakeUp(string $name, string $email, ?Role $role): int
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->where('email', $email))
            ?? (new Admin)->forceFill(['name' => $name, 'password' => Str::password(64), 'email_verified_at' => null]);

        if ($admin->exists) {
            // An account that signed in before any fiche named it keeps its name and its password, and is in service.
            $admin->email_verified_at ??= CarbonImmutable::now();
        }

        $role ??= $admin->exists ? $admin->role : Role::Collaborator;
        $this->assertAnOwnerStays($admin, $role, $owners);

        $admin->forceFill(['email' => $email, 'role' => $role])->save();

        return (int) $admin->getKey();
    }

    /**
     * Keeps the account to the address of its fiche, and to the role chosen
     * there · its name is its own.
     *
     * @throws RuntimeException when the role would leave no owner in service
     */
    public function keep(int $accountId, string $email, ?Role $role): void
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->whereKey($accountId));

        if ($admin === null) {
            return;
        }

        if (Str::lower($admin->email) !== Str::lower($email)) {
            $this->links->forgetTheLinksOf($admin);
            $admin->invited_at = $admin->email_verified_at === null ? null : $admin->invited_at;
        }

        $role ??= $admin->role;
        $this->assertAnOwnerStays($admin, $role, $owners);

        $admin->forceFill(['email' => $email, 'role' => $role])->save();
    }

    /** Cuts the account off, or opens it again · the last owner and whoever is acting stay open. */
    public function suspend(int $accountId, bool $suspended): void
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->whereKey($accountId));

        if ($admin === null || ($suspended && $this->standing->staysOpen($admin, self::soleOf($owners)))) {
            return;
        }

        // A device kept signed in comes back with no password once the account is restored · its key is changed.
        $admin->forceFill($suspended
            ? ['suspended_at' => CarbonImmutable::now(), 'remember_token' => Str::random(60)]
            : ['suspended_at' => null])->save();
    }

    /** Removes the account and its links · the last owner and whoever is acting stay. */
    public function remove(int $accountId): void
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->whereKey($accountId));

        if ($admin === null || $this->standing->staysOpen($admin, self::soleOf($owners))) {
            return;
        }

        $this->links->forgetTheLinksOf($admin);
        $admin->delete();
    }

    /**
     * @param  Builder<Admin>  $query
     */
    private function lockedFirst(Builder $query): ?Admin
    {
        $query->getQuery()->lockForUpdate();

        return $query->first();
    }

    /**
     * Refuses, under the owners' lock, a role that would leave no owner in
     * service · what the fiche asked before saving held two, and another
     * write took one meanwhile.
     *
     * @param  list<int>  $owners
     *
     * @throws RuntimeException
     */
    private function assertAnOwnerStays(Admin $account, Role $role, array $owners): void
    {
        if ($role !== Role::Owner && $owners === [$account->id]) {
            throw new RuntimeException('Account '.$account->id.' is the last owner in service, and keeps the role.');
        }
    }

    /**
     * The only owner of these, or null when there are several.
     *
     * @param  list<int>  $owners
     */
    private static function soleOf(array $owners): ?int
    {
        return count($owners) === 1 ? $owners[0] : null;
    }
}
