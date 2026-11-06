<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Booking\Accounts;
use App\Enums\Account\Role;
use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Actions\Admin\Team\ArchivePractitionerAction;
use Falcon\Booking\Actions\Admin\Team\DeletePractitionerAction;
use Falcon\Booking\Data\Account\AccountData;
use Falcon\Booking\Data\Account\AccountStatusData;
use Falcon\Booking\Enums\Account\NoticeTone;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What the site answers booking about each account · where it stands, what
 * may not change, and what archiving or removing a fiche does to it. Booking
 * asks by number, and the site knows no practitioner.
 */
final class EachAccessIsReadAndGuardedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_site_lets_each_account_choose_its_own_password(): void
    {
        $this->assertFalse($this->accounts()->asksForAPassword());
    }

    public function test_each_status_is_read_from_the_accounts_in_one_reading(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-06 10:00', 'Europe/Paris'));
        $now = CarbonImmutable::now();
        $accounts = [
            'active' => $this->anAccountWith(['email_verified_at' => $now->subDay()]),
            'invited' => $this->anAccountWith(['email_verified_at' => null, 'invited_at' => $now->subDays(7)->addMinute()]),
            'expired' => $this->anAccountWith(['email_verified_at' => null, 'invited_at' => $now->subDays(7)->subMinute()]),
            'failed' => $this->anAccountWith(['email_verified_at' => null, 'invited_at' => $now->subHour(), 'invitation_failed_at' => $now]),
            'never' => $this->anAccountWith(['email_verified_at' => null, 'invited_at' => null]),
            'suspended' => $this->anAccountWith(['email_verified_at' => null, 'invited_at' => $now->subHour(), 'suspended_at' => $now]),
        ];

        $reads = $this->queriesDuring(fn (): array => $this->accounts()->statusesOf([...array_values(array_map(static fn (Admin $account): int => $account->id, $accounts)), 999]), $statuses);

        $this->assertEquals(new AccountStatusData(null, false, null), $statuses[$accounts['active']->id]);
        $this->assertEquals(new AccountStatusData('Invitation envoyée le 30 octobre', false, 'Renvoyer l’invitation'), $statuses[$accounts['invited']->id]);
        $this->assertEquals(new AccountStatusData('Invitation expirée', true, 'Renvoyer l’invitation'), $statuses[$accounts['expired']->id]);
        $this->assertEquals(new AccountStatusData('L’invitation n’a pas pu partir', true, 'Renvoyer l’invitation'), $statuses[$accounts['failed']->id]);
        $this->assertEquals(new AccountStatusData('L’invitation n’a pas pu partir', false, 'Renvoyer l’invitation'), $statuses[$accounts['never']->id]);
        $this->assertNull($statuses[$accounts['suspended']->id]->sendAgain, 'Nothing leaves for a suspended account.');
        $this->assertArrayNotHasKey(999, $statuses);
        $this->assertSame(2, $reads, 'The accounts and the sole owner, nothing per account.');
        $this->assertSame([], $this->accounts()->statusesOf([]));
    }

    /** The day an invitation left is the site's, late in the evening as in the morning. */
    public function test_the_day_of_an_invitation_is_read_in_the_site_s_zone(): void
    {
        $account = $this->anAccountWith(['email_verified_at' => null, 'invited_at' => CarbonImmutable::parse('2026-11-05 23:30', 'Europe/Paris')]);
        $this->travelTo(CarbonImmutable::parse('2026-11-06 08:00', 'Europe/Paris'));

        $this->assertSame('Invitation envoyée le 5 novembre', $this->accounts()->statusesOf([$account->id])[$account->id]->said);
    }

    public function test_an_owner_may_change_any_role_but_the_own(): void
    {
        $other = Admin::factory()->create();
        $owner = Admin::factory()->create();
        $this->actingAs($owner, 'admin');

        $statuses = $this->accounts()->statusesOf([$owner->id, $other->id]);

        $this->assertSame('Vous ne pouvez pas changer votre propre accès.', $statuses[$owner->id]->roleStays);
        $this->assertNull($statuses[$other->id]->roleStays);
        $this->assertFalse($statuses[$owner->id]->leavingClosesIt, 'Whoever acts is never cut off.');
        $this->assertTrue($statuses[$other->id]->leavingClosesIt);
    }

    public function test_a_collaborator_changes_no_role_and_gives_no_access(): void
    {
        $owner = Admin::factory()->create();
        $this->actingAs($this->anAccountWith([]), 'admin');

        $this->assertSame('Seul un propriétaire peut changer un accès.', $this->accounts()->statusesOf([$owner->id])[$owner->id]->roleStays);
        $this->assertSame('Seul un propriétaire peut donner un accès.', $this->accounts()->refusalFor(null, $this->data('louise@exemple.fr', 'collaborator'))?->message);
    }

    public function test_a_role_nobody_offers_is_refused_on_its_row(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $refusal = $this->accounts()->refusalFor(null, $this->data('louise@exemple.fr', 'superuser'));

        $this->assertSame('Choisissez un accès proposé.', $refusal?->message);
        $this->assertTrue($refusal->aboutTheRole);
        $this->assertNull($this->accounts()->refusalFor(null, $this->data('louise@exemple.fr', 'collaborator')));
    }

    public function test_an_address_another_account_holds_is_refused_on_the_address(): void
    {
        $this->actingAs(Admin::factory()->create(['name' => 'Amandine', 'email' => 'amandine@exemple.fr']), 'admin');
        $account = $this->anAccountWith([]);

        $refusal = $this->accounts()->refusalFor($account->id, $this->data('amandine@exemple.fr', 'collaborator'));

        $this->assertSame('Cette adresse est déjà prise par Amandine.', $refusal?->message);
        $this->assertTrue($refusal->aboutTheAddress);
        $this->assertNull($this->accounts()->refusalFor($account->id, $this->data($account->email, 'collaborator')), 'Its own address is no one else’s.');
    }

    public function test_a_new_account_is_a_collaborator_s_unless_the_fiche_names_a_role(): void
    {
        $chosen = $this->accounts()->create(new AccountData('Louise Bernard', 'louise@exemple.fr', role: 'owner'));
        $unnamed = $this->accounts()->create(new AccountData('Marc Petit', 'marc@exemple.fr'));

        $this->assertSame(Role::Owner, Admin::query()->findOrFail($chosen)->role);
        $this->assertSame(Role::Collaborator, Admin::query()->findOrFail($unnamed)->role);
        $this->assertNull(Admin::query()->findOrFail($unnamed)->email_verified_at, 'It waits for its invitation.');
    }

    /** Without anybody signed in, a command or a job, the last owner still keeps the office. */
    public function test_the_last_owner_is_never_demoted_cut_off_nor_removed(): void
    {
        Admin::factory()->create(['suspended_at' => CarbonImmutable::now()]);
        $owner = Admin::factory()->create();
        $fiche = Practitioner::factory()->create(['email' => $owner->email, 'account_id' => $owner->id]);

        $this->assertSame('Il faut au moins un propriétaire.', $this->accounts()->statusesOf([$owner->id])[$owner->id]->roleStays);
        $this->assertSame('Il faut au moins un propriétaire.', $this->accounts()->refusalFor($owner->id, $this->data($owner->email, 'collaborator'))?->message);
        $this->assertFalse($this->accounts()->statusesOf([$owner->id])[$owner->id]->leavingClosesIt);

        app(ArchivePractitionerAction::class)->execute($fiche, true);
        $this->assertNull($owner->fresh()?->suspended_at);

        app(DeletePractitionerAction::class)->execute($fiche);
        $this->assertNotNull($owner->fresh());
    }

    public function test_nothing_leaves_for_a_suspended_account(): void
    {
        Mail::fake();
        $account = $this->anAccountWith(['email_verified_at' => null, 'invited_at' => null, 'suspended_at' => CarbonImmutable::now()]);

        $this->assertNull($this->accounts()->announce($account->id, null));
        $this->assertSame(NoticeTone::Info, $this->accounts()->sendAgain($account->id)->tone);
        Mail::assertNothingSent();
    }

    public function test_whoever_acts_is_never_cut_off(): void
    {
        $owner = Admin::factory()->create();
        Admin::factory()->create();
        $this->actingAs($owner, 'admin');

        app(ArchivePractitionerAction::class)->execute(Practitioner::factory()->create(['email' => $owner->email, 'account_id' => $owner->id]), true);

        $this->assertNull($owner->fresh()?->suspended_at);
    }

    public function test_archiving_cuts_the_access_and_restoring_gives_it_back_as_it_was(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        [$member, $account] = $this->aMemberWith([]);

        app(ArchivePractitionerAction::class)->execute($member, true);
        $this->assertNotNull($account->fresh()?->suspended_at);

        app(ArchivePractitionerAction::class)->execute($member->fresh() ?? $member, false);
        $restored = $account->fresh();
        $this->assertNull($restored->suspended_at);
        $this->assertSame($account->password, $restored->password);
    }

    /** A device kept signed in does not come back with the account once it is restored. */
    public function test_archiving_forgets_the_devices_kept_signed_in(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        [$member, $account] = $this->aMemberWith(['remember_token' => 'un-appareil-garde']);
        $accounts = Auth::createUserProvider('admins');
        $this->assertNotNull($accounts);

        $this->assertNotNull($accounts->retrieveByToken($account->id, 'un-appareil-garde'));

        app(ArchivePractitionerAction::class)->execute($member, true);
        app(ArchivePractitionerAction::class)->execute($member->fresh() ?? $member, false);

        $this->assertNull($accounts->retrieveByToken($account->id, 'un-appareil-garde'));
    }

    public function test_removing_a_member_removes_the_account_and_its_links(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        [$member, $account] = $this->aMemberWith(['email_verified_at' => null]);
        app(PasswordLinksService::class)->links(PasswordLinksService::INVITATIONS)->createToken($account);

        app(DeletePractitionerAction::class)->execute($member);

        $this->assertNull($account->fresh());
        $this->assertDatabaseMissing('admin_invitation_tokens', ['email' => $account->email]);
    }

    public function test_the_profile_knows_an_account_a_fiche_keeps(): void
    {
        [, $kept] = $this->aMemberWith([]);
        $alone = Admin::factory()->create();

        $this->assertTrue($this->accounts()->aFicheKeeps($kept->id));
        $this->assertFalse($this->accounts()->aFicheKeeps($alone->id));
    }

    /**
     * A collaborator's account.
     *
     * @param  array<string, mixed>  $columns
     */
    private function anAccountWith(array $columns): Admin
    {
        return Admin::factory()->collaborator()->create($columns);
    }

    /**
     * A member whose fiche keeps a collaborator's account.
     *
     * @param  array<string, mixed>  $columns
     * @return array{0: Practitioner, 1: Admin}
     */
    private function aMemberWith(array $columns): array
    {
        $account = $this->anAccountWith($columns);

        return [Practitioner::factory()->create(['email' => $account->email, 'account_id' => $account->id]), $account];
    }

    private function data(string $email, string $role): AccountData
    {
        return new AccountData('Louise Bernard', $email, role: $role);
    }

    /**
     * The queries a gesture makes, and what it gives.
     *
     * @template T
     *
     * @param  callable(): T  $gesture
     *
     * @param-out T  $given
     */
    private function queriesDuring(callable $gesture, mixed &$given = null): int
    {
        $queries = 0;
        $this->app->make('db')->listen(static function () use (&$queries): void {
            $queries++;
        });

        $given = $gesture();

        return $queries;
    }

    private function accounts(): Accounts
    {
        return app(Accounts::class);
    }
}
