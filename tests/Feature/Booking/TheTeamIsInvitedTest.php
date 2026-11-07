<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\Account\Role;
use App\Livewire\Auth\Password\ChoosePasswordForm;
use App\Mail\Auth\AddressChangedMail;
use App\Mail\Auth\InvitationMail;
use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Livewire\Admin\Settings\PractitionerForm;
use Falcon\Booking\Livewire\Admin\Settings\PractitionerIndex;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * The fiche gives a member of the team an account: booking asks the site to
 * create it and keeps its number, the invitation leaves once the fiche is
 * saved, and an address stays one person's.
 */
final class TheTeamIsInvitedTest extends TestCase
{
    use RefreshDatabase;

    private Admin $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://chouchoute-toi.fr']);
        Mail::fake();

        $this->owner = Admin::factory()->create(['name' => 'Amandine', 'email' => 'amandine@exemple.fr']);
        $this->actingAs($this->owner, 'admin');
    }

    public function test_the_fiche_asks_for_no_first_password(): void
    {
        $this->aFilledFiche()->assertDontSee('Mot de passe provisoire');
    }

    public function test_a_new_member_receives_an_invitation_that_names_no_address(): void
    {
        $this->aFilledFiche()
            ->call('savePractitioner')
            ->assertReturned(true)
            ->assertDispatched('ui-toast', type: 'success', title: 'Praticien enregistré', description: 'L’invitation est partie à julie@exemple.fr.');

        $account = $this->accountOf(Practitioner::query()->sole());

        $this->assertSame(Role::Collaborator, $account->role);
        $this->assertSame(['Julie Martin', 'julie@exemple.fr'], [$account->name, $account->email]);
        $this->assertNull($account->email_verified_at);
        $this->assertNotNull($account->invited_at);

        $link = $this->linkSentTo('julie@exemple.fr');

        $this->assertStringStartsWith('https://chouchoute-toi.fr/admin/bienvenue/'.$account->id.'/', $link);
        $this->assertStringNotContainsString('julie', $link);
        Mail::assertSent(InvitationMail::class, static fn (InvitationMail $mail): bool => $mail->days === 7);
    }

    public function test_the_role_chosen_on_the_fiche_is_the_account_s(): void
    {
        $this->aFilledFiche()->set('accessRole', 'owner')->call('savePractitioner')->assertReturned(true);

        $this->assertSame(Role::Owner, $this->accountOf(Practitioner::query()->sole())->role);
    }

    /** A host name the request carries never reaches the link, whatever the server answers to. */
    public function test_the_link_is_built_on_the_site_address_whatever_the_request_names(): void
    {
        URL::forceRootUrl('https://ailleurs.example');

        $this->aFilledFiche()->call('savePractitioner');

        $this->assertStringStartsWith('https://chouchoute-toi.fr/admin/bienvenue/', $this->linkSentTo('julie@exemple.fr'));
    }

    public function test_the_invitation_reads_as_a_message_of_the_site(): void
    {
        $html = (new InvitationMail('Julie Martin', 'julie@exemple.fr', 'https://chouchoute-toi.fr/admin/bienvenue/3/abc', 7))->render();

        $this->assertStringContainsString('Bienvenue, Julie Martin', $html);
        $this->assertStringContainsString('href="https://chouchoute-toi.fr/admin/bienvenue/3/abc"', $html);
        $this->assertStringContainsString('valable 7 jours', $html);
        $this->assertStringContainsString((string) config('mail.from.name'), $html);
        $this->assertStringNotContainsString('—', $html);
    }

    public function test_an_invitation_that_cannot_leave_is_noted_and_said(): void
    {
        $log = Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('The relay refused the message.'));

        $this->aFilledFiche()
            ->call('savePractitioner')
            ->assertReturned(true)
            ->assertDispatched('ui-toast', type: 'error', title: 'Praticien enregistré', description: 'L’invitation n’a pas pu partir. Renvoyez-la depuis le menu de sa ligne.');

        $account = Admin::query()->where('email', 'julie@exemple.fr')->sole();

        $this->assertNotNull($account->invitation_failed_at);
        $this->assertNull($account->invited_at);
        $log->shouldHaveReceived('error');
    }

    /** The minute between two invitations does not hold for one that never left. */
    public function test_an_invitation_that_did_not_leave_can_leave_again_at_once(): void
    {
        $account = Admin::factory()->collaborator()->create([
            'email' => 'julie@exemple.fr',
            'email_verified_at' => null,
            'invited_at' => CarbonImmutable::now(),
            'invitation_failed_at' => CarbonImmutable::now(),
        ]);
        $member = Practitioner::factory()->create(['email' => 'julie@exemple.fr', 'account_id' => $account->id]);

        Livewire::test(PractitionerIndex::class)
            ->call('sendTheAccessAgain', $member->id)
            ->assertDispatched('ui-toast', type: 'success', title: 'L’invitation est repartie à julie@exemple.fr.');

        $this->assertNull($account->fresh()?->invitation_failed_at);
        Mail::assertSent(InvitationMail::class, 1);
    }

    public function test_sending_again_waits_a_minute_then_replaces_the_link(): void
    {
        $this->aFilledFiche()->call('savePractitioner');
        $member = Practitioner::query()->sole();
        $first = $this->linkSentTo('julie@exemple.fr');

        Livewire::test(PractitionerIndex::class)
            ->call('sendTheAccessAgain', $member->id)
            ->assertDispatched('ui-toast', type: 'info', title: 'Une invitation vient de partir. Réessayez dans une minute.');

        Mail::assertSentCount(1);

        $this->travel(61)->seconds();

        Livewire::test(PractitionerIndex::class)
            ->call('sendTheAccessAgain', $member->id)
            ->assertDispatched('ui-toast', type: 'success', title: 'L’invitation est repartie à julie@exemple.fr.');

        Mail::assertSentCount(2);
        $this->assertFalse($this->linkServes($first));
    }

    public function test_an_account_already_in_service_is_not_invited_again(): void
    {
        $account = Admin::factory()->collaborator()->create(['email' => 'julie@exemple.fr']);
        $member = Practitioner::factory()->create(['email' => 'julie@exemple.fr', 'account_id' => $account->id]);

        Livewire::test(PractitionerIndex::class)
            ->call('sendTheAccessAgain', $member->id)
            ->assertDispatched('ui-toast', type: 'info', title: 'Ce compte n’attend aucune invitation.');

        Mail::assertNothingSent();
    }

    /** The name of the practitioner is booking's, the name of the account the site's · a fiche saved again leaves the second alone. */
    public function test_saving_a_member_again_keeps_the_account_s_name_and_sends_nothing(): void
    {
        $waiting = $this->aMemberWithAnAccount('Julie Martin', 'julie@exemple.fr', ['email_verified_at' => null, 'invited_at' => CarbonImmutable::now()]);
        $inService = $this->aMemberWithAnAccount('Camille Arnaud', 'camille@exemple.fr');

        $this->theFicheOf($waiting)->set('name', 'Julie Petit')->call('savePractitioner')->assertReturned(true);
        $this->theFicheOf($inService)->call('savePractitioner')->assertReturned(true);

        Mail::assertNothingSent();
        $this->assertSame('Julie Petit', $waiting->fresh()?->getAttribute('name'));
        $this->assertSame('Julie Martin', $this->accountOf($waiting)->name);
    }

    /** An account that existed before the fiche keeps the name it signs in under · only a new account takes the fiche's. */
    public function test_an_account_taken_up_keeps_its_name(): void
    {
        Admin::factory()->create(['name' => 'Cam', 'email' => 'camille@exemple.fr']);

        $this->aNewFiche()
            ->set('name', 'Camille Arnaud')
            ->set('email', 'camille@exemple.fr')
            ->set('giveAnAccess', true)
            ->set('accessRole', 'collaborator')
            ->call('savePractitioner')
            ->assertReturned(true);

        $this->assertSame('Cam', Admin::query()->where('email', 'camille@exemple.fr')->sole()->name);
        $this->assertSame('Camille Arnaud', Practitioner::query()->sole()->getAttribute('name'));
    }

    /** The account that signed in before any fiche named it keeps its password, and nothing leaves. */
    public function test_the_owner_s_own_fiche_takes_up_the_account_at_that_address(): void
    {
        $password = $this->owner->password;

        $this->aNewFiche()
            ->set('name', 'Amandine David')
            ->set('email', 'amandine@exemple.fr')
            ->set('giveAnAccess', true)
            ->set('accessRole', 'owner')
            ->call('savePractitioner')
            ->assertReturned(true);

        $account = Admin::query()->sole();

        $this->assertSame($account->id, Practitioner::query()->sole()->getAttribute('account_id'));
        $this->assertSame([Role::Owner, $password], [$account->role, $account->password]);
        $this->assertNotNull($account->email_verified_at);
        Mail::assertNothingSent();
    }

    public function test_an_account_taken_up_is_in_service_even_if_it_never_said_so(): void
    {
        Admin::factory()->create(['email' => 'camille@exemple.fr', 'email_verified_at' => null]);

        $this->aNewFiche()
            ->set('name', 'Camille Arnaud')
            ->set('email', 'camille@exemple.fr')
            ->set('giveAnAccess', true)
            ->set('accessRole', 'owner')
            ->call('savePractitioner')
            ->assertReturned(true);

        $this->assertNotNull(Admin::query()->where('email', 'camille@exemple.fr')->sole()->email_verified_at);
        Mail::assertNothingSent();
    }

    public function test_the_owner_cannot_take_up_the_own_account_as_a_collaborator(): void
    {
        $this->aNewFiche()
            ->set('name', 'Amandine David')
            ->set('email', 'amandine@exemple.fr')
            ->set('giveAnAccess', true)
            ->set('accessRole', 'collaborator')
            ->call('savePractitioner')
            ->assertReturned(false)
            ->assertHasErrors('accessRole')
            ->assertSee('Vous ne pouvez pas changer votre propre accès.');

        $this->assertSame(Role::Owner, $this->owner->fresh()?->role);
        $this->assertSame(0, Practitioner::query()->count());
    }

    public function test_an_address_another_fiche_holds_is_refused_by_name(): void
    {
        Practitioner::factory()->create(['name' => 'Camille Arnaud', 'email' => 'julie@exemple.fr']);

        $this->aFilledFiche()
            ->call('savePractitioner')
            ->assertReturned(false)
            ->assertHasErrors('email')
            ->assertSee('Cette adresse est déjà prise par Camille Arnaud.');

        $this->assertSame(1, Admin::query()->count());
    }

    public function test_an_address_another_account_holds_is_refused_by_name(): void
    {
        $member = $this->aMemberWithAnAccount('Julie Martin', 'julie@exemple.fr');

        $this->theFicheOf($member)
            ->set('email', 'amandine@exemple.fr')
            ->call('savePractitioner')
            ->assertReturned(false)
            ->assertHasErrors('email')
            ->assertSee('Cette adresse est déjà prise par Amandine.');
    }

    public function test_correcting_a_pending_address_sends_a_new_link_and_kills_the_old(): void
    {
        $this->aFilledFiche()->call('savePractitioner');
        $member = Practitioner::query()->sole();
        $old = $this->linkSentTo('julie@exemple.fr');

        $this->theFicheOf($member)
            ->set('email', 'julie.martin@exemple.fr')
            ->call('savePractitioner')
            ->assertReturned(true)
            ->assertDispatched('ui-toast', type: 'success', title: 'Praticien enregistré', description: 'L’invitation est partie à julie.martin@exemple.fr.');

        $this->assertFalse($this->linkServes($old));
        $this->assertTrue($this->linkServes($this->linkSentTo('julie.martin@exemple.fr')));
        Mail::assertNotSent(AddressChangedMail::class);
    }

    public function test_correcting_the_address_of_an_account_in_service_tells_both(): void
    {
        $member = $this->aMemberWithAnAccount('Julie Martin', 'julie@exemple.fr');
        $account = $this->accountOf($member);
        app(PasswordLinksService::class)->links(PasswordLinksService::PASSWORD_RESETS)->createToken($account);

        $this->theFicheOf($member)
            ->set('email', 'julie.martin@exemple.fr')
            ->call('savePractitioner')
            ->assertReturned(true)
            ->assertDispatched('ui-toast', type: 'success', title: 'Praticien enregistré', description: 'Ses deux adresses sont prévenues · il se connecte désormais avec julie.martin@exemple.fr.');

        Mail::assertSent(AddressChangedMail::class, static fn (AddressChangedMail $mail): bool => $mail->hasTo('julie@exemple.fr') && $mail->hasTo('julie.martin@exemple.fr'));
        Mail::assertNotSent(InvitationMail::class);
        $this->assertSame(['julie.martin@exemple.fr', $account->password], [$account->fresh()?->email, $account->fresh()?->password]);
        $this->assertDatabaseMissing('admin_password_reset_tokens', ['email' => 'julie@exemple.fr']);
    }

    /** The message of a new address that does not leave is said, with the address to sign in with. */
    public function test_a_new_address_that_cannot_be_told_is_said(): void
    {
        $member = $this->aMemberWithAnAccount('Julie Martin', 'julie@exemple.fr');
        Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('The relay refused the message.'));

        $this->theFicheOf($member)
            ->set('email', 'julie.martin@exemple.fr')
            ->call('savePractitioner')
            ->assertReturned(true)
            ->assertDispatched('ui-toast', type: 'error', title: 'Praticien enregistré', description: 'Le message qui le prévient du changement d’adresse n’a pas pu partir. Dites-lui de se connecter avec julie.martin@exemple.fr.');

        $this->assertSame('julie.martin@exemple.fr', $this->accountOf($member)->email);
    }

    public function test_the_message_of_a_new_address_offers_no_link(): void
    {
        $html = (new AddressChangedMail('Julie Martin', 'julie.martin@exemple.fr'))->render();

        $this->assertStringContainsString('avec l’adresse julie.martin@exemple.fr', $html);
        $this->assertStringContainsString('Votre mot de passe ne change pas.', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    private function aNewFiche(): Testable
    {
        return Livewire::test(PractitionerForm::class)->call('editPractitioner');
    }

    private function aFilledFiche(): Testable
    {
        return $this->aNewFiche()
            ->set('name', 'Julie Martin')
            ->set('email', 'julie@exemple.fr')
            ->set('giveAnAccess', true)
            ->set('accessRole', 'collaborator');
    }

    private function theFicheOf(Practitioner $member): Testable
    {
        return Livewire::test(PractitionerForm::class)->call('editPractitioner', $member->id);
    }

    /**
     * A member whose fiche keeps a collaborator's account at the same address.
     *
     * @param  array<string, mixed>  $account
     */
    private function aMemberWithAnAccount(string $name, string $email, array $account = []): Practitioner
    {
        $admin = Admin::factory()->collaborator()->create(['name' => $name, 'email' => $email, ...$account]);

        return Practitioner::factory()->create(['name' => $name, 'email' => $email, 'account_id' => $admin->id]);
    }

    private function accountOf(Practitioner $member): Admin
    {
        return Admin::query()->findOrFail((int) $member->fresh()?->getAttribute('account_id'));
    }

    /** The link of the last invitation sent to this address. */
    private function linkSentTo(string $address): string
    {
        $links = Mail::sent(InvitationMail::class, static fn (InvitationMail $mail): bool => $mail->hasTo($address))
            ->map(static fn (InvitationMail $mail): string => $mail->link);

        $this->assertNotEmpty($links, 'No invitation left for '.$address.'.');

        return (string) $links->last();
    }

    /** Whether the page this link opens still offers to choose a password. */
    private function linkServes(string $link): bool
    {
        $this->assertSame(1, preg_match('#/admin/bienvenue/(\d+)/([^/]+)$#', $link, $parts));

        return (bool) Livewire::test(ChoosePasswordForm::class, ['account' => (int) $parts[1], 'token' => $parts[2], 'purpose' => 'invitation'])->get('usable');
    }
}
