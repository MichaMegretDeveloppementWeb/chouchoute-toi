<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\Account\Role;
use App\Models\Admin;
use Falcon\Booking\Livewire\Admin\Settings\PractitionerForm;
use Falcon\Booking\Livewire\Admin\Settings\PractitionerIndex;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The owner becomes a member of the team from the account signed in · booking
 * keeps that account to the fiche through the site's class, which creates none
 * and sends nothing · and leaving the team never shuts it, whoever does it.
 */
final class TheOwnerJoinsTheTeamTest extends TestCase
{
    use RefreshDatabase;

    private Admin $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->owner = Admin::factory()->create(['name' => 'Amandine', 'email' => 'amandine@exemple.fr']);
        $this->actingAs($this->owner, 'admin');
    }

    public function test_the_owner_joins_the_team_and_the_account_is_left_as_it_was(): void
    {
        $before = $this->owner->fresh()?->getAttributes();

        Livewire::test(PractitionerIndex::class)->assertSee('M’ajouter comme praticien');

        Livewire::test(PractitionerForm::class)
            ->call('joinTheTeam')
            ->assertReturned(true)
            ->assertSee('Votre compte')
            ->assertSee('Vous ne pouvez pas changer votre propre accès.')
            ->call('savePractitioner')
            ->assertHasNoErrors()
            ->assertReturned(true);

        $member = Practitioner::query()->sole();

        $this->assertSame($this->owner->id, $member->getAttribute('account_id'));
        $this->assertTrue($member->getAttribute('joined_an_account'));
        $this->assertSame('amandine@exemple.fr', $member->getAttribute('email'));
        $this->assertSame(1, Admin::query()->count(), 'An account was created.');
        $this->assertSame($before, $this->owner->fresh()?->getAttributes(), 'The account changed.');
        Mail::assertNothingSent();

        Livewire::test(PractitionerIndex::class)->assertDontSee('M’ajouter comme praticien');
    }

    /** Another owner archives, then removes, the fiche the first one joined · the first stays in service. */
    public function test_another_owner_leaving_it_never_shuts_the_account(): void
    {
        Livewire::test(PractitionerForm::class)->call('joinTheTeam')->call('savePractitioner')->assertReturned(true);
        Practitioner::factory()->create();
        $member = Practitioner::query()->where('account_id', $this->owner->id)->sole();

        $this->actingAs(Admin::factory()->create(), 'admin');

        Livewire::test(PractitionerIndex::class)
            ->call('askToArchive', $member->id)
            ->assertReturned(null);

        $this->assertNull($this->owner->refresh()->suspended_at);

        Livewire::test(PractitionerIndex::class)->call('restore', $member->id);
        Livewire::test(PractitionerIndex::class)->call('askToRemove', $member->id)->call('remove');

        $this->assertNull(Practitioner::query()->find($member->id));
        $this->assertSame(Role::Owner, $this->owner->refresh()->role);
        $this->assertNull($this->owner->suspended_at);
        Mail::assertNothingSent();
    }
}
