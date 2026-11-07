<?php

declare(strict_types=1);

namespace App\Services\Auth\Account;

use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/**
 * Where an account of the back office stands · what the team's list says of
 * it, who may change its role, and which account stays open.
 */
final readonly class AccountStandingService
{
    public function __construct(private PasswordLinksService $links) {}

    /** What the list says of an account · nothing for one in service. */
    public function said(Admin $account): ?string
    {
        return match (true) {
            $account->email_verified_at !== null => null,
            $account->invitation_failed_at !== null, $account->invited_at === null => 'L’invitation n’a pas pu partir',
            $this->hasExpired($account) => 'Invitation expirée',
            default => 'Invitation envoyée le '.$account->invited_at->translatedFormat('j F'),
        };
    }

    /** Whether an account still waiting for its first sign-in has an invitation that failed or expired. */
    public function isInTrouble(Admin $account): bool
    {
        return $account->email_verified_at === null && ($account->invitation_failed_at !== null || $this->hasExpired($account));
    }

    /** Whether an invitation may leave again for this account · it waits for its first sign-in, and is in service. */
    public function awaitsAnInvitation(Admin $account): bool
    {
        return $account->email_verified_at === null && $account->suspended_at === null;
    }

    /** Why this account's role cannot change here, in a sentence, or null. */
    public function whyItStays(Admin $account, ?int $soleOwner): ?string
    {
        $actor = $this->actor();

        return match (true) {
            $actor?->is($account) === true => 'Vous ne pouvez pas changer votre propre accès.',
            $actor?->isOwner() === false => 'Seul un propriétaire peut changer un accès.',
            $account->id === $soleOwner => 'Il faut au moins un propriétaire.',
            default => null,
        };
    }

    /** Whether this account is never cut off nor removed · the last owner, and whoever is acting. */
    public function staysOpen(Admin $account, ?int $soleOwner): bool
    {
        return $this->actor()?->is($account) === true || $account->id === $soleOwner;
    }

    /** The only owner still in service, or null when there are several. */
    public function soleOwnerId(): ?int
    {
        $query = Admin::query()->where('role', Role::Owner)->where('suspended_at', null);
        $query->getQuery()->limit(2);
        $owners = $query->pluck('id');

        return $owners->count() === 1 ? (int) $owners->first() : null;
    }

    /** The name of the other account that already holds this address, or null. */
    public function holderOf(string $email, int $accountId): ?string
    {
        $other = Admin::query()->where('email', $email)->whereKeyNot($accountId)->value('name');

        return is_string($other) ? $other : null;
    }

    /** The account signed in to the back office, or null. */
    public function actor(): ?Admin
    {
        $user = Auth::guard('admin')->user();

        return $user instanceof Admin ? $user : null;
    }

    private function hasExpired(Admin $account): bool
    {
        $expiresAfter = $this->links->minutesOf(PasswordLinksService::INVITATIONS);

        return $account->invited_at?->lt(CarbonImmutable::now()->subMinutes($expiresAfter)) === true;
    }
}
