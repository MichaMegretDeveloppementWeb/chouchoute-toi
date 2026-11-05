<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\Team\AdminRole;
use App\Models\Admin;
use Carbon\CarbonImmutable;
use Falcon\Booking\Contracts\Admin\Team\ManagesTeamAccess;
use Falcon\Booking\Data\Team\AccessLevelData;
use Falcon\Booking\Data\Team\AccessRefusalData;
use Falcon\Booking\Data\Team\MemberAccessData;
use Falcon\Booking\Enums\Team\AccessNotice;
use Falcon\Booking\Enums\Team\AccessState;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * The accounts of the team, as booking's screens ask for them: a member of the
 * team is an account of the back-office, invited by mail, and the address of
 * the fiche is the one the member signs in with.
 */
final readonly class SiteTeamAccessService implements ManagesTeamAccess
{
    public function __construct(private TeamMessagesService $messages) {}

    public function isOffered(): bool
    {
        return true;
    }

    public function levels(): array
    {
        return array_map(
            static fn (AdminRole $role): AccessLevelData => new AccessLevelData($role->value, $role->label(), $role->opens()),
            [AdminRole::Practitioner, AdminRole::Owner],
        );
    }

    public function accessOf(array $practitionerIds): array
    {
        if ($practitionerIds === []) {
            return [];
        }

        $query = Admin::query();
        $query->getQuery()->whereIn('practitioner_id', $practitionerIds);
        $accounts = $query->get(['id', 'name', 'email', 'role', 'practitioner_id', 'email_verified_at', 'invited_at', 'invitation_failed_at']);

        $soleOwner = $this->soleOwnerId();
        $access = [];

        foreach ($accounts as $account) {
            $access[(int) $account->practitioner_id] = new MemberAccessData(
                $this->stateOf($account),
                $account->role->value,
                $account->invited_at,
                $this->whyItStays($account, $soleOwner),
            );
        }

        return $access;
    }

    public function refusalFor(?Practitioner $member, string $email, string $level): ?AccessRefusalData
    {
        $role = AdminRole::tryFrom($level);

        if ($role === null) {
            return new AccessRefusalData('Choisissez un accès proposé.');
        }

        if ($this->actor()?->isOwner() === false) {
            return new AccessRefusalData('Seul un propriétaire peut donner un accès.');
        }

        $account = ($member === null ? null : Admin::query()->where('practitioner_id', $member->id)->first())
            ?? $this->unlinkedAccountAt($email)->first();
        $why = $account === null || $account->role === $role ? null : $this->whyItStays($account, $this->soleOwnerId());

        if ($why !== null) {
            return new AccessRefusalData($why);
        }

        $holder = $this->holderOf($email, $member, $account);

        return $holder === null ? null : new AccessRefusalData('Cette adresse est déjà prise par '.$holder.'.', aboutTheAddress: true);
    }

    public function write(Practitioner $member, string $level): void
    {
        $email = (string) $member->getAttribute('email');

        $account = $this->lockedFirst(Admin::query()->where('practitioner_id', $member->id))
            ?? $this->lockedFirst($this->unlinkedAccountAt($email))
            ?? (new Admin)->forceFill(['password' => Str::password(64), 'email_verified_at' => null]);

        if ($account->exists && $account->practitioner_id === null) {
            // An account that signed in before any fiche named it keeps its password.
            $account->email_verified_at ??= CarbonImmutable::now();
        } elseif ($account->exists && Str::lower($account->email) !== Str::lower($email)) {
            $this->messages->forgetTheLinksOf($account);
            $account->invited_at = $account->email_verified_at === null ? null : $account->invited_at;
        }

        $account->forceFill([
            'name' => (string) $member->getAttribute('name'),
            'email' => $email,
            'role' => AdminRole::from($level),
            'practitioner_id' => $member->id,
        ])->save();
    }

    public function announce(Practitioner $member, ?string $formerAddress): AccessNotice
    {
        $account = Admin::query()->where('practitioner_id', $member->id)->first();

        if ($account === null || $account->suspended_at !== null) {
            return AccessNotice::Nothing;
        }

        if ($account->email_verified_at === null) {
            return $account->invited_at === null ? $this->messages->sendTheInvitation($account) : AccessNotice::Nothing;
        }

        if ($formerAddress !== null && Str::lower($formerAddress) !== Str::lower($account->email)) {
            $this->messages->tellOfTheNewAddress($account, $formerAddress);
        }

        return AccessNotice::Nothing;
    }

    public function invite(Practitioner $member): AccessNotice
    {
        $account = Admin::query()->where('practitioner_id', $member->id)->first();

        if ($account === null || $account->email_verified_at !== null || $account->suspended_at !== null) {
            return AccessNotice::Nothing;
        }

        if ($account->invitation_failed_at === null && $account->invited_at?->gt(CarbonImmutable::now()->subMinute()) === true) {
            return AccessNotice::InvitationTooSoon;
        }

        return $this->messages->sendTheInvitation($account);
    }

    public function archived(Practitioner $member, bool $archived): void
    {
        $account = Admin::query()->where('practitioner_id', $member->id)->first();

        if ($account === null || ($archived && $this->staysOpen($account))) {
            return;
        }

        $account->forceFill(['suspended_at' => $archived ? CarbonImmutable::now() : null])->save();
    }

    public function removed(Practitioner $member): void
    {
        $account = Admin::query()->where('practitioner_id', $member->id)->first();

        if ($account === null || $this->staysOpen($account)) {
            return;
        }

        $this->messages->forgetTheLinksOf($account);
        $account->delete();
    }

    /** Where an account stands, read from its columns. */
    private function stateOf(Admin $account): AccessState
    {
        $expiresAfter = (int) config('auth.passwords.'.TeamMessagesService::INVITATIONS.'.expire');

        return match (true) {
            $account->email_verified_at !== null => AccessState::Active,
            $account->invitation_failed_at !== null, $account->invited_at === null => AccessState::InvitationNotSent,
            $account->invited_at->lt(CarbonImmutable::now()->subMinutes($expiresAfter)) => AccessState::InvitationExpired,
            default => AccessState::Invited,
        };
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
    private function staysOpen(Admin $account): bool
    {
        return $this->actor()?->is($account) === true || $account->id === $this->soleOwnerId();
    }

    /** The name that already holds this address, other than the member and the account it takes up, or null. */
    private function holderOf(string $email, ?Practitioner $member, ?Admin $account): ?string
    {
        $fiche = Practitioner::query()
            ->where('email', $email)
            ->when($member !== null, static fn (Builder $query): Builder => $query->whereKeyNot($member?->id))
            ->value('name');

        if (is_string($fiche)) {
            return $fiche;
        }

        $other = Admin::query()
            ->where('email', $email)
            ->when($account !== null, static fn (Builder $query): Builder => $query->whereKeyNot($account?->id))
            ->value('name');

        return is_string($other) ? $other : null;
    }

    /**
     * The account at this address that no fiche names yet · the first member
     * whose fiche carries the address takes it up.
     *
     * @return Builder<Admin>
     */
    private function unlinkedAccountAt(string $email): Builder
    {
        return Admin::query()->where('email', $email)->where('practitioner_id', null);
    }

    /**
     * @param  Builder<Admin>  $query
     */
    private function lockedFirst(Builder $query): ?Admin
    {
        $query->getQuery()->lockForUpdate();

        return $query->first();
    }

    /** The only owner still in service, or null when there are several. */
    private function soleOwnerId(): ?int
    {
        $query = Admin::query()->where('role', AdminRole::Owner)->where('suspended_at', null);
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
