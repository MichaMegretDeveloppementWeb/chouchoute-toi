<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Actions\Admin\Team\ArchivePractitionerAction;
use Falcon\Booking\Actions\Admin\Team\DeletePractitionerAction;
use Falcon\Booking\Contracts\Admin\Team\ManagesTeamAccess;
use Falcon\Booking\Data\Team\AccessLevelData;
use Falcon\Booking\Enums\Team\AccessNotice;
use Falcon\Booking\Enums\Team\AccessState;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What the site answers booking about each member · where the account stands,
 * what may not change, and what archiving or removing a fiche does to it.
 */
final class EachAccessIsReadAndGuardedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_levels_are_offered_from_the_narrowest(): void
    {
        $this->assertEquals(
            [['practitioner', 'Praticien'], ['owner', 'Propriétaire']],
            array_map(static fn (AccessLevelData $level): array => [$level->value, $level->label], $this->access()->levels()),
        );
    }

    public function test_each_state_is_read_from_the_account_in_one_reading(): void
    {
        $now = CarbonImmutable::now();
        $members = [
            'active' => $this->aMemberWith(['email_verified_at' => $now->subDay()]),
            'invited' => $this->aMemberWith(['email_verified_at' => null, 'invited_at' => $now->subDays(7)->addMinute()]),
            'expired' => $this->aMemberWith(['email_verified_at' => null, 'invited_at' => $now->subDays(7)->subMinute()]),
            'failed' => $this->aMemberWith(['email_verified_at' => null, 'invited_at' => $now->subHour(), 'invitation_failed_at' => $now]),
            'never' => $this->aMemberWith(['email_verified_at' => null, 'invited_at' => null]),
        ];
        $nobody = Practitioner::factory()->create();

        $access = $this->access()->accessOf([...array_values(array_map(static fn (Practitioner $member): int => $member->id, $members)), $nobody->id]);

        $this->assertSame(AccessState::Active, $access[$members['active']->id]->state);
        $this->assertSame(AccessState::Invited, $access[$members['invited']->id]->state);
        $this->assertSame(AccessState::InvitationExpired, $access[$members['expired']->id]->state);
        $this->assertSame(AccessState::InvitationNotSent, $access[$members['failed']->id]->state);
        $this->assertSame(AccessState::InvitationNotSent, $access[$members['never']->id]->state);
        $this->assertSame('practitioner', $access[$members['active']->id]->level);
        $this->assertArrayNotHasKey($nobody->id, $access);
        $this->assertSame([], $this->access()->accessOf([]));
    }

    public function test_an_owner_may_change_any_access_but_the_own(): void
    {
        $other = $this->anOwnerWithAFiche();
        $owner = $this->anOwnerWithAFiche();
        $this->actingAs($owner, 'admin');

        $access = $this->access()->accessOf([(int) $owner->practitioner_id, (int) $other->practitioner_id]);

        $this->assertSame('Vous ne pouvez pas changer votre propre accès.', $access[(int) $owner->practitioner_id]->lockedBecause);
        $this->assertNull($access[(int) $other->practitioner_id]->lockedBecause);
    }

    public function test_a_practitioner_changes_no_access_and_gives_none(): void
    {
        $owner = $this->anOwnerWithAFiche();
        $member = $this->aMemberWith([]);
        $this->actingAs(Admin::query()->where('practitioner_id', $member->id)->sole(), 'admin');

        $this->assertSame('Seul un propriétaire peut changer un accès.', $this->access()->accessOf([(int) $owner->practitioner_id])[(int) $owner->practitioner_id]->lockedBecause);
        $this->assertSame('Seul un propriétaire peut donner un accès.', $this->access()->refusalFor(null, 'louise@exemple.fr', 'practitioner')?->message);
    }

    public function test_a_level_nobody_offers_is_refused(): void
    {
        $this->actingAs($this->anOwnerWithAFiche(), 'admin');

        $this->assertSame('Choisissez un accès proposé.', $this->access()->refusalFor(null, 'louise@exemple.fr', 'superuser')?->message);
        $this->assertNull($this->access()->refusalFor(null, 'louise@exemple.fr', 'practitioner'));
    }

    /** Without anybody signed in, a command or a job, the last owner still keeps the office. */
    public function test_the_last_owner_is_never_demoted_cut_off_nor_removed(): void
    {
        Admin::factory()->create(['suspended_at' => CarbonImmutable::now()]);
        $owner = $this->anOwnerWithAFiche();
        $fiche = Practitioner::query()->findOrFail($owner->practitioner_id);

        $this->assertSame('Il faut au moins un propriétaire.', $this->access()->accessOf([$fiche->id])[$fiche->id]->lockedBecause);
        $this->assertSame('Il faut au moins un propriétaire.', $this->access()->refusalFor($fiche, $owner->email, 'practitioner')?->message);

        app(ArchivePractitionerAction::class)->execute($fiche, true);
        $this->assertNull($owner->fresh()?->suspended_at);

        app(DeletePractitionerAction::class)->execute($fiche);
        $this->assertNotNull($owner->fresh());
        $this->assertNull($owner->fresh()->practitioner_id);
    }

    public function test_nothing_leaves_for_a_suspended_account(): void
    {
        Mail::fake();
        $member = $this->aMemberWith(['email_verified_at' => null, 'invited_at' => null, 'suspended_at' => CarbonImmutable::now()]);

        $this->assertSame(AccessNotice::Nothing, $this->access()->announce($member, null));
        $this->assertSame(AccessNotice::Nothing, $this->access()->invite($member));
        Mail::assertNothingSent();
    }

    public function test_whoever_acts_is_never_cut_off(): void
    {
        $owner = $this->anOwnerWithAFiche();
        $this->anOwnerWithAFiche();
        $this->actingAs($owner, 'admin');

        app(ArchivePractitionerAction::class)->execute(Practitioner::query()->findOrFail($owner->practitioner_id), true);

        $this->assertNull($owner->fresh()?->suspended_at);
    }

    public function test_archiving_cuts_the_access_and_restoring_gives_it_back_as_it_was(): void
    {
        $this->actingAs($this->anOwnerWithAFiche(), 'admin');
        $member = $this->aMemberWith([]);
        $account = Admin::query()->where('practitioner_id', $member->id)->sole();

        app(ArchivePractitionerAction::class)->execute($member, true);
        $this->assertNotNull($account->fresh()?->suspended_at);

        app(ArchivePractitionerAction::class)->execute($member, false);
        $restored = $account->fresh();
        $this->assertNull($restored->suspended_at);
        $this->assertSame($account->password, $restored->password);
    }

    /** A device kept signed in does not come back with the account once it is restored. */
    public function test_archiving_forgets_the_devices_kept_signed_in(): void
    {
        $this->actingAs($this->anOwnerWithAFiche(), 'admin');
        $member = $this->aMemberWith(['remember_token' => 'un-appareil-garde']);
        $account = Admin::query()->where('practitioner_id', $member->id)->sole();
        $accounts = Auth::createUserProvider('admins');
        $this->assertNotNull($accounts);

        $this->assertNotNull($accounts->retrieveByToken($account->id, 'un-appareil-garde'));

        app(ArchivePractitionerAction::class)->execute($member, true);
        app(ArchivePractitionerAction::class)->execute($member, false);

        $this->assertNull($accounts->retrieveByToken($account->id, 'un-appareil-garde'));
    }

    public function test_removing_a_member_removes_the_account_and_its_links(): void
    {
        $this->actingAs($this->anOwnerWithAFiche(), 'admin');
        $member = $this->aMemberWith(['email_verified_at' => null]);
        $account = Admin::query()->where('practitioner_id', $member->id)->sole();
        app(PasswordLinksService::class)->links(PasswordLinksService::INVITATIONS)->createToken($account);

        app(DeletePractitionerAction::class)->execute($member);

        $this->assertNull($account->fresh());
        $this->assertDatabaseMissing('admin_invitation_tokens', ['email' => $account->email]);
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function aMemberWith(array $account): Practitioner
    {
        $member = Practitioner::factory()->create();
        Admin::factory()->practitioner($member->id)->create(['email' => (string) $member->getAttribute('email'), ...$account]);

        return $member;
    }

    private function anOwnerWithAFiche(): Admin
    {
        $fiche = Practitioner::factory()->create();

        return Admin::factory()->create(['email' => (string) $fiche->getAttribute('email'), 'practitioner_id' => $fiche->id]);
    }

    private function access(): ManagesTeamAccess
    {
        return app(ManagesTeamAccess::class);
    }
}
