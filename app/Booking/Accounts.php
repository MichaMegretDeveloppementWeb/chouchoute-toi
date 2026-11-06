<?php

declare(strict_types=1);

namespace App\Booking;

use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Account\AccountMailService;
use App\Services\Auth\Account\OwnerLockService;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Contracts\Admin\Account\ProvidesAccounts;
use Falcon\Booking\Data\Account\AccountData;
use Falcon\Booking\Data\Account\AccountNoticeData;
use Falcon\Booking\Data\Account\AccountRefusalData;
use Falcon\Booking\Data\Account\AccountStatusData;
use Falcon\Booking\Enums\Account\NoticeTone;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The accounts booking asks the site for · an account of the back-office,
 * invited by mail, signing in with the address booking gives it. The site
 * chooses no first password · the stored one is drawn at random and never
 * given.
 */
final readonly class Accounts implements ProvidesAccounts
{
    public function __construct(
        private AccountMailService $mail,
        private PasswordLinksService $links,
        private OwnerLockService $owners,
    ) {}

    public function asksForAPassword(): bool
    {
        return false;
    }

    public function refusalFor(?int $accountId, AccountData $account): ?AccountRefusalData
    {
        $role = Role::tryFrom((string) $account->role);

        if ($account->role !== null && $role === null) {
            return new AccountRefusalData('Choisissez un accès proposé.', aboutTheRole: true);
        }

        if ($this->actor()?->isOwner() === false) {
            return new AccountRefusalData('Seul un propriétaire peut donner un accès.');
        }

        $existing = $accountId === null ? $this->accountAt($account->email)->first() : Admin::query()->find($accountId);
        $why = $existing === null || $role === null || $existing->role === $role ? null : $this->whyItStays($existing, $this->soleOwnerId());

        if ($why !== null) {
            return new AccountRefusalData($why, aboutTheRole: true);
        }

        $holder = $accountId === null ? null : $this->holderOf($account->email, $accountId);

        return $holder === null ? null : new AccountRefusalData('Cette adresse est déjà prise par '.$holder.'.', aboutTheAddress: true);
    }

    public function create(AccountData $account): int
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst($this->accountAt($account->email))
            ?? (new Admin)->forceFill(['password' => Str::password(64), 'email_verified_at' => null]);

        if ($admin->exists) {
            // An account that signed in before any fiche named it keeps its password, and is in service.
            $admin->email_verified_at ??= CarbonImmutable::now();
        }

        $role = Role::tryFrom((string) $account->role) ?? ($admin->exists ? $admin->role : Role::Collaborator);
        $this->assertAnOwnerStays($admin, $role, $owners);

        $admin->forceFill([
            'name' => $account->name,
            'email' => $account->email,
            'role' => $role,
        ])->save();

        return (int) $admin->getKey();
    }

    public function update(int $accountId, AccountData $account): void
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->whereKey($accountId));

        if ($admin === null) {
            return;
        }

        if (Str::lower($admin->email) !== Str::lower($account->email)) {
            $this->links->forgetTheLinksOf($admin);
            $admin->invited_at = $admin->email_verified_at === null ? null : $admin->invited_at;
        }

        $role = Role::tryFrom((string) $account->role) ?? $admin->role;
        $this->assertAnOwnerStays($admin, $role, $owners);

        $admin->forceFill([
            'name' => $account->name,
            'email' => $account->email,
            'role' => $role,
        ])->save();
    }

    public function suspended(int $accountId, bool $suspended): void
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->whereKey($accountId));

        if ($admin === null || ($suspended && $this->staysOpen($admin, $this->soleOf($owners)))) {
            return;
        }

        // A device kept signed in comes back with no password once the account is restored · its key is changed.
        $admin->forceFill($suspended
            ? ['suspended_at' => CarbonImmutable::now(), 'remember_token' => Str::random(60)]
            : ['suspended_at' => null])->save();
    }

    public function removed(int $accountId): void
    {
        $owners = $this->owners->holdTheOwners();
        $admin = $this->lockedFirst(Admin::query()->whereKey($accountId));

        if ($admin === null || $this->staysOpen($admin, $this->soleOf($owners))) {
            return;
        }

        $this->links->forgetTheLinksOf($admin);
        $admin->delete();
    }

    public function statusesOf(array $accountIds): array
    {
        if ($accountIds === []) {
            return [];
        }

        $accounts = Admin::query()->whereKey($accountIds)
            ->get(['id', 'role', 'email_verified_at', 'invited_at', 'invitation_failed_at', 'suspended_at']);

        $soleOwner = $this->soleOwnerId();
        $statuses = [];

        foreach ($accounts as $account) {
            $awaitsItsFirstSignIn = $account->email_verified_at === null;

            $statuses[$account->id] = new AccountStatusData(
                $this->said($account),
                $awaitsItsFirstSignIn && ($account->invitation_failed_at !== null || $this->hasExpired($account)),
                $awaitsItsFirstSignIn && $account->suspended_at === null ? 'Renvoyer l’invitation' : null,
                ! $this->staysOpen($account, $soleOwner),
                $this->whyItStays($account, $soleOwner),
            );
        }

        return $statuses;
    }

    public function announce(int $accountId, ?string $formerAddress): ?AccountNoticeData
    {
        $admin = Admin::query()->find($accountId);

        if ($admin === null || $admin->suspended_at !== null) {
            return null;
        }

        if ($admin->email_verified_at === null) {
            return $admin->invited_at === null ? $this->invitationOf($admin, 'L’invitation est partie à ') : null;
        }

        if ($formerAddress === null || Str::lower($formerAddress) === Str::lower($admin->email)) {
            return null;
        }

        return $this->mail->tellOfTheNewAddress($admin, $formerAddress)
            ? new AccountNoticeData('Ses deux adresses sont prévenues · il se connecte désormais avec '.$admin->email.'.')
            : new AccountNoticeData('Le message qui le prévient du changement d’adresse n’a pas pu partir. Dites-lui de se connecter avec '.$admin->email.'.', NoticeTone::Error);
    }

    public function sendAgain(int $accountId): AccountNoticeData
    {
        $admin = Admin::query()->find($accountId);

        if ($admin === null || $admin->email_verified_at !== null || $admin->suspended_at !== null) {
            return new AccountNoticeData('Ce compte n’attend aucune invitation.', NoticeTone::Info);
        }

        if ($admin->invitation_failed_at === null && $admin->invited_at?->gt(CarbonImmutable::now()->subMinute()) === true) {
            return new AccountNoticeData('Une invitation vient de partir. Réessayez dans une minute.', NoticeTone::Info);
        }

        return $this->invitationOf($admin, 'L’invitation est repartie à ');
    }

    /** Whether a fiche of booking keeps this account · its address is then that fiche's, and changes there. */
    public function aFicheKeeps(int $accountId): bool
    {
        $query = Practitioner::query()->where('account_id', $accountId);

        return $query->getQuery()->exists();
    }

    /** Sends the invitation, and says what became of it. */
    private function invitationOf(Admin $account, string $sentTo): AccountNoticeData
    {
        return $this->mail->sendTheInvitation($account)
            ? new AccountNoticeData($sentTo.$account->email.'.')
            : new AccountNoticeData('L’invitation n’a pas pu partir. Renvoyez-la depuis le menu de sa ligne.', NoticeTone::Error);
    }

    /** What the list says of an account · nothing for one in service. */
    private function said(Admin $account): ?string
    {
        return match (true) {
            $account->email_verified_at !== null => null,
            $account->invitation_failed_at !== null, $account->invited_at === null => 'L’invitation n’a pas pu partir',
            $this->hasExpired($account) => 'Invitation expirée',
            default => 'Invitation envoyée le '.$account->invited_at->translatedFormat('j F'),
        };
    }

    private function hasExpired(Admin $account): bool
    {
        $expiresAfter = $this->links->minutesOf(PasswordLinksService::INVITATIONS);

        return $account->invited_at?->lt(CarbonImmutable::now()->subMinutes($expiresAfter)) === true;
    }

    /** Why this account's role cannot change here, in a sentence, or null. */
    private function whyItStays(Admin $account, ?int $soleOwner): ?string
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
    private function staysOpen(Admin $account, ?int $soleOwner): bool
    {
        return $this->actor()?->is($account) === true || $account->id === $soleOwner;
    }

    /** The name of the other account that already holds this address, or null. */
    private function holderOf(string $email, int $accountId): ?string
    {
        $other = Admin::query()->where('email', $email)->whereKeyNot($accountId)->value('name');

        return is_string($other) ? $other : null;
    }

    /**
     * The account at this address · a fiche that gives an access to an address
     * an account already signs in with takes that account up.
     *
     * @return Builder<Admin>
     */
    private function accountAt(string $email): Builder
    {
        return Admin::query()->where('email', $email);
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
    private function soleOf(array $owners): ?int
    {
        return count($owners) === 1 ? $owners[0] : null;
    }

    /** The only owner still in service, or null when there are several. */
    private function soleOwnerId(): ?int
    {
        $query = Admin::query()->where('role', Role::Owner)->where('suspended_at', null);
        $query->getQuery()->limit(2);
        $owners = $query->pluck('id');

        return $owners->count() === 1 ? (int) $owners->first() : null;
    }

    private function actor(): ?Admin
    {
        $user = Auth::guard('admin')->user();

        return $user instanceof Admin ? $user : null;
    }
}
