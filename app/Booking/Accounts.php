<?php

declare(strict_types=1);

namespace App\Booking;

use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Account\AccountMailService;
use App\Services\Auth\Account\AccountStandingService;
use App\Services\Auth\Account\AccountWriteService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Contracts\Admin\Account\ProvidesAccounts;
use Falcon\Booking\Data\Account\AccountData;
use Falcon\Booking\Data\Account\AccountNoticeData;
use Falcon\Booking\Data\Account\AccountRefusalData;
use Falcon\Booking\Data\Account\AccountStatusData;
use Falcon\Booking\Enums\Account\NoticeTone;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Support\Str;

/**
 * The accounts booking asks the site for · an account of the back-office,
 * invited by mail, signing in with the address booking gives it. The site
 * chooses no first password · the stored one is drawn at random and never
 * given. What booking asks is said in the site's words here, and decided by
 * the services of the site's accounts.
 */
final readonly class Accounts implements ProvidesAccounts
{
    public function __construct(
        private AccountWriteService $accounts,
        private AccountStandingService $standing,
        private AccountMailService $mail,
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

        if ($this->standing->actor()?->isOwner() === false) {
            return new AccountRefusalData('Seul un propriétaire peut donner un accès.');
        }

        $existing = $accountId === null ? Admin::query()->where('email', $account->email)->first() : Admin::query()->find($accountId);
        $why = $existing === null || $role === null || $existing->role === $role ? null : $this->standing->whyItStays($existing, $this->standing->soleOwnerId());

        if ($why !== null) {
            return new AccountRefusalData($why, aboutTheRole: true);
        }

        $holder = $accountId === null ? null : $this->standing->holderOf($account->email, $accountId);

        return $holder === null ? null : new AccountRefusalData('Cette adresse est déjà prise par '.$holder.'.', aboutTheAddress: true);
    }

    public function create(AccountData $account): int
    {
        return $this->accounts->createOrTakeUp($account->name, $account->email, Role::tryFrom((string) $account->role));
    }

    public function update(int $accountId, AccountData $account): void
    {
        $this->accounts->keep($accountId, $account->email, Role::tryFrom((string) $account->role));
    }

    public function suspended(int $accountId, bool $suspended): void
    {
        $this->accounts->suspend($accountId, $suspended);
    }

    public function removed(int $accountId): void
    {
        $this->accounts->remove($accountId);
    }

    public function statusesOf(array $accountIds): array
    {
        if ($accountIds === []) {
            return [];
        }

        $accounts = Admin::query()->whereKey($accountIds)
            ->get(['id', 'role', 'email_verified_at', 'invited_at', 'invitation_failed_at', 'suspended_at']);

        $soleOwner = $this->standing->soleOwnerId();
        $statuses = [];

        foreach ($accounts as $account) {
            $statuses[$account->id] = new AccountStatusData(
                $this->standing->said($account),
                $this->standing->isInTrouble($account),
                $this->standing->awaitsAnInvitation($account) ? 'Renvoyer l’invitation' : null,
                ! $this->standing->staysOpen($account, $soleOwner),
                $this->standing->whyItStays($account, $soleOwner),
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
}
