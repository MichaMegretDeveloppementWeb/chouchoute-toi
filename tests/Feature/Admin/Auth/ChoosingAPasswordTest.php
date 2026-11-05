<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Livewire\Admin\Auth\ChoosePasswordForm;
use App\Models\Admin;
use App\Services\Team\TeamMessagesService;
use Carbon\CarbonImmutable;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A link chooses a password once, for the account it names, within its time ·
 * and a link that cannot serve says so, whatever the reason.
 */
final class ChoosingAPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'un-mot-de-passe-solide';

    private Admin $member;

    protected function setUp(): void
    {
        parent::setUp();

        $fiche = Practitioner::factory()->create(['email' => 'julie@exemple.fr']);
        $this->member = Admin::factory()->practitioner($fiche->id)->create([
            'email' => 'julie@exemple.fr',
            'email_verified_at' => null,
            'invited_at' => CarbonImmutable::now(),
        ]);
    }

    public function test_the_page_of_an_invitation_names_the_address_and_hands_no_secret_on(): void
    {
        $this->get(route('admin.invitation', ['account' => $this->member->id, 'token' => $this->anInvitation()]))
            ->assertOk()
            ->assertSee('julie@exemple.fr')
            ->assertSee('Choisir mon mot de passe')
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
    }

    public function test_choosing_the_password_signs_in_and_leads_to_the_page_of_the_role(): void
    {
        $this->theForm($this->anInvitation())
            ->set('password', self::PASSWORD)
            ->set('passwordConfirmation', self::PASSWORD)
            ->call('choose')
            ->assertHasNoErrors()
            ->assertRedirect(route('booking.admin.agenda'));

        $account = $this->member->fresh();

        $this->assertTrue(Hash::check(self::PASSWORD, (string) $account?->password));
        $this->assertNotNull($account?->email_verified_at);
        $this->assertSame($this->member->id, Auth::guard('admin')->id());
    }

    public function test_a_link_serves_once(): void
    {
        $token = $this->anInvitation();

        $this->theForm($token)->set('password', self::PASSWORD)->set('passwordConfirmation', self::PASSWORD)->call('choose');

        $this->theForm($token)->assertSet('usable', false)->assertSee('Ce lien a expiré ou a déjà servi.');
    }

    public function test_an_invitation_lives_seven_days(): void
    {
        $token = $this->anInvitation();

        $this->travel(7 * 24 * 60 - 1)->minutes();
        $this->theForm($token)->assertSet('usable', true);

        $this->travel(2)->minutes();
        $this->theForm($token)->assertSet('usable', false);
    }

    public function test_the_link_of_a_suspended_account_serves_nothing(): void
    {
        $token = $this->anInvitation();
        $this->member->forceFill(['suspended_at' => CarbonImmutable::now()])->save();

        $this->theForm($token)
            ->assertSet('usable', false)
            ->set('password', self::PASSWORD)
            ->set('passwordConfirmation', self::PASSWORD)
            ->call('choose');

        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_a_link_names_one_account_and_one_purpose(): void
    {
        $token = $this->anInvitation();
        $other = Admin::factory()->create();

        $this->assertFalse((bool) Livewire::test(ChoosePasswordForm::class, ['account' => $other->id, 'token' => $token, 'purpose' => 'invitation'])->get('usable'));
        $this->assertFalse((bool) Livewire::test(ChoosePasswordForm::class, ['account' => $this->member->id, 'token' => $token, 'purpose' => 'reset'])->get('usable'));
        $this->assertFalse((bool) $this->theForm('not-the-token')->get('usable'));
    }

    public function test_a_forgotten_password_keeps_the_day_the_account_came_into_service(): void
    {
        $since = CarbonImmutable::parse('2026-09-01 10:00:00');
        $this->member->forceFill(['email_verified_at' => $since, 'remember_token' => 'souvenir-d-un-appareil'])->save();
        $token = app(TeamMessagesService::class)->links(TeamMessagesService::PASSWORD_RESETS)->createToken($this->member);

        Livewire::test(ChoosePasswordForm::class, ['account' => $this->member->id, 'token' => $token, 'purpose' => 'reset'])
            ->assertSee('Choisissez un nouveau mot de passe')
            ->set('password', self::PASSWORD)
            ->set('passwordConfirmation', self::PASSWORD)
            ->call('choose')
            ->assertRedirect(route('booking.admin.agenda'));

        $this->assertEquals($since, $this->member->fresh()?->email_verified_at);
        $this->assertNotSame('souvenir-d-un-appareil', $this->member->fresh()?->remember_token);
        $this->assertTrue(Hash::check(self::PASSWORD, (string) $this->member->fresh()?->password));
    }

    public function test_the_password_holds_twelve_characters_typed_twice(): void
    {
        $this->theForm($this->anInvitation())
            ->set('password', 'trop-court')
            ->set('passwordConfirmation', 'trop-court')
            ->call('choose')
            ->assertHasErrors(['password' => 'Le mot de passe doit faire au moins 12 caractères.'])
            ->set('password', self::PASSWORD)
            ->set('passwordConfirmation', 'un-autre-mot-de-passe')
            ->call('choose')
            ->assertHasErrors(['passwordConfirmation' => 'La confirmation ne correspond pas au mot de passe.']);

        $this->assertNull($this->member->fresh()?->email_verified_at);
    }

    public function test_a_link_tried_too_often_waits(): void
    {
        $form = $this->theForm('not-the-token')->set('password', self::PASSWORD)->set('passwordConfirmation', self::PASSWORD);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $form->call('choose')->assertHasNoErrors();
        }

        $form->call('choose')->assertHasErrors('password');

        $this->assertStringStartsWith('Trop de tentatives.', (string) $form->errors()->first('password'));
    }

    private function anInvitation(): string
    {
        return app(TeamMessagesService::class)->links(TeamMessagesService::INVITATIONS)->createToken($this->member);
    }

    private function theForm(string $token): Testable
    {
        return Livewire::test(ChoosePasswordForm::class, ['account' => $this->member->id, 'token' => $token, 'purpose' => 'invitation']);
    }
}
