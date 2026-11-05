<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Team;

use App\Models\Admin;
use App\Providers\GateServiceProvider;
use Carbon\CarbonImmutable;
use Falcon\Analytics\Enums\Authorization\Ability as AnalyticsAbility;
use Falcon\Booking\Enums\Authorization\Ability as BookingAbility;
use Falcon\Booking\Livewire\Admin\AppointmentForm;
use Falcon\Booking\Models\Appointment;
use Falcon\Booking\Models\AppointmentLine;
use Falcon\Booking\Models\Location;
use Falcon\Booking\Models\Practitioner;
use Falcon\Booking\Models\Service;
use Falcon\Booking\Models\Unavailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * An owner has everything, and a practitioner the planning and the clients ·
 * asked of the screens and of the gestures, the packages leaving it to the site.
 */
final class EachRoleOpensWhatItShouldTest extends TestCase
{
    use RefreshDatabase;

    private Practitioner $julie;

    private Practitioner $camille;

    private Admin $owner;

    private Admin $practitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->julie = Practitioner::factory()->create(['name' => 'Julie Martin', 'position' => 1]);
        $this->camille = Practitioner::factory()->create(['name' => 'Camille Arnaud', 'position' => 2]);
        $this->owner = Admin::factory()->create();
        $this->practitioner = Admin::factory()->practitioner($this->julie->id)->create();
    }

    /** @return array<string, array{string}> */
    public static function provideOpenToBoth(): array
    {
        return [
            'planning' => ['booking.admin.agenda'],
            'clients' => ['booking.admin.clients'],
            'profile' => ['admin.profile'],
        ];
    }

    #[DataProvider('provideOpenToBoth')]
    public function test_both_roles_reach_these_screens(string $route): void
    {
        $this->actingAs($this->owner, 'admin')->get(route($route))->assertOk();
        $this->actingAs($this->practitioner, 'admin')->get(route($route))->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function provideOwnerOnly(): array
    {
        return [
            'journal' => ['booking.admin.journal'],
            'messages' => ['booking.admin.messages'],
            'treatments' => ['booking.admin.catalogue'],
            'categories' => ['booking.admin.categories'],
            'practitioners' => ['booking.admin.settings.practitioners'],
            'establishment' => ['booking.admin.settings'],
            'hours' => ['booking.admin.schedule'],
            'slots' => ['booking.admin.settings.slots'],
            'booking window' => ['booking.admin.settings.booking-window'],
            'notifications' => ['booking.admin.settings.notifications'],
            'audience' => ['analytics.admin.overview'],
            'marketing' => ['analytics.admin.marketing.dashboard'],
        ];
    }

    #[DataProvider('provideOwnerOnly')]
    public function test_only_an_owner_reaches_these_screens(string $route): void
    {
        $this->actingAs($this->owner, 'admin')->get(route($route))->assertOk();
        $this->actingAs($this->practitioner, 'admin')->get(route($route))->assertForbidden();
    }

    public function test_the_dashboard_is_the_owners(): void
    {
        $this->assertTrue(Gate::forUser($this->owner)->allows(GateServiceProvider::DASHBOARD));
        $this->assertFalse(Gate::forUser($this->practitioner)->allows(GateServiceProvider::DASHBOARD));
    }

    public function test_every_ability_of_analytics_is_closed_to_a_practitioner(): void
    {
        foreach (AnalyticsAbility::cases() as $ability) {
            $this->assertTrue(Gate::forUser($this->owner)->allows($ability), $ability->value);
            $this->assertFalse(Gate::forUser($this->practitioner)->allows($ability), $ability->value);
        }
    }

    /** @return array<string, array{BookingAbility, bool}> */
    public static function provideWhatAPractitionerAsks(): array
    {
        return [
            'clients' => [BookingAbility::Clients, true],
            'changing a client' => [BookingAbility::ClientsEdit, true],
            'erasing a client' => [BookingAbility::ClientsErase, false],
            'attachments' => [BookingAbility::Attachments, true],
            'changing attachments' => [BookingAbility::AttachmentsEdit, true],
            'creating a visit' => [BookingAbility::AppointmentsEdit, true],
            'creating a block' => [BookingAbility::UnavailabilitiesEdit, true],
            'the settings' => [BookingAbility::Settings, false],
            'the sms account' => [BookingAbility::SmsAccount, false],
        ];
    }

    #[DataProvider('provideWhatAPractitionerAsks')]
    public function test_what_a_practitioner_may_ask(BookingAbility $ability, bool $allowed): void
    {
        $this->assertSame($allowed, Gate::forUser($this->practitioner)->allows($ability));
        $this->assertTrue(Gate::forUser($this->owner)->allows($ability));
    }

    public function test_a_practitioner_changes_only_what_he_performs(): void
    {
        $hers = $this->visitOf($this->julie);
        $theirs = $this->visitOf($this->camille);
        $shared = $this->visitOf($this->camille);

        AppointmentLine::factory()->create([
            'appointment_id' => $shared->id,
            'practitioner_id' => $this->julie->id,
            'starts_at' => $shared->starts_at,
            'ends_at' => $shared->ends_at,
            'occupied_until' => $shared->ends_at,
            'position' => 2,
        ]);

        foreach ([BookingAbility::AppointmentsEdit, BookingAbility::AppointmentsCancel, BookingAbility::AppointmentsDelete] as $gesture) {
            $this->assertTrue(Gate::forUser($this->practitioner)->allows($gesture, $hers), $gesture->value);
            $this->assertTrue(Gate::forUser($this->practitioner)->allows($gesture, $shared), $gesture->value);
            $this->assertFalse(Gate::forUser($this->practitioner)->allows($gesture, $theirs), $gesture->value);
            $this->assertTrue(Gate::forUser($this->owner)->allows($gesture, $theirs), $gesture->value);
        }
    }

    public function test_a_practitioner_names_only_himself(): void
    {
        $this->assertTrue(Gate::forUser($this->practitioner)->allows(BookingAbility::AppointmentsAssign, $this->julie));
        $this->assertFalse(Gate::forUser($this->practitioner)->allows(BookingAbility::AppointmentsAssign, $this->camille));
        $this->assertTrue(Gate::forUser($this->owner)->allows(BookingAbility::AppointmentsAssign, $this->camille));
    }

    public function test_a_practitioner_blocks_only_himself_and_closes_no_place(): void
    {
        $place = Location::factory()->create();

        $hers = Unavailability::factory()->blocking($this->julie)->create();
        $withAPlace = Unavailability::factory()->blocking($this->julie)->closing($place)->create();
        $theirs = Unavailability::factory()->blocking($this->camille)->create();

        $this->assertTrue(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesEdit, $hers));
        $this->assertFalse(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesEdit, $withAPlace));
        $this->assertFalse(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesEdit, $theirs));

        $this->assertTrue(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesAssign, $this->julie));
        $this->assertFalse(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesAssign, $this->camille));
        $this->assertFalse(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesAssign, $place));
        $this->assertTrue(Gate::forUser($this->owner)->allows(BookingAbility::UnavailabilitiesAssign, $place));
    }

    /** Asked without an object, as the report of abilities asks, every rule answers. */
    public function test_every_rule_answers_without_an_object(): void
    {
        foreach ([$this->owner, $this->practitioner] as $account) {
            $this->assertSame(0, Artisan::call('booking:abilities', ['--user' => $account->id]));
        }

        $this->assertTrue(Gate::forUser($this->practitioner)->allows(BookingAbility::AppointmentsAssign));
        $this->assertTrue(Gate::forUser($this->practitioner)->allows(BookingAbility::UnavailabilitiesAssign));
    }

    public function test_an_account_of_the_team_with_no_fiche_changes_nothing_already_written(): void
    {
        $nobody = Admin::factory()->create(['role' => 'practitioner', 'practitioner_id' => null]);
        $visit = $this->visitOf($this->julie);
        $block = Unavailability::factory()->blocking($this->julie)->create();

        $this->assertFalse(Gate::forUser($nobody)->allows(BookingAbility::AppointmentsEdit, $visit));
        $this->assertFalse(Gate::forUser($nobody)->allows(BookingAbility::UnavailabilitiesEdit, $block));
    }

    /** The fiche says it too: only herself to name, and another's visit in read-only. */
    public function test_the_fiche_of_a_practitioner_offers_only_herself(): void
    {
        $this->actingAs($this->practitioner, 'admin');

        Livewire::test(AppointmentForm::class)
            ->call('newAppointment')
            ->assertSet('entryPractitionerIds', [(string) $this->julie->id])
            ->assertSee('Julie Martin')
            ->assertDontSee('Camille Arnaud');

        Livewire::test(AppointmentForm::class)
            ->call('openAppointment', $this->visitOf($this->camille)->id)
            ->assertSeeHtml('<fieldset class="fb:contents" disabled');

        Livewire::test(AppointmentForm::class)
            ->call('openAppointment', $this->visitOf($this->julie)->id)
            ->assertDontSeeHtml('<fieldset class="fb:contents" disabled');
    }

    /** Closing the treatments' screen does not close what the fiche needs to book one. */
    public function test_a_practitioner_still_finds_the_treatments_in_the_fiche(): void
    {
        $service = Service::factory()->create(['name' => 'Pose complète']);

        $this->actingAs($this->practitioner, 'admin');

        Livewire::test(AppointmentForm::class)
            ->call('newAppointment')
            ->set('entryServiceIds.0', (string) $service->id)
            ->assertSee('Pose complète')
            ->assertHasNoErrors();
    }

    private function visitOf(Practitioner $person): Appointment
    {
        return Appointment::factory()
            ->startingAt(CarbonImmutable::now()->addWeek()->setTime(10, 0), durationMinutes: 60)
            ->create(['practitioner_id' => $person->id]);
    }
}
