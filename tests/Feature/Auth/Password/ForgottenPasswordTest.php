<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Password;

use App\Livewire\Auth\Password\ChoosePasswordForm;
use App\Livewire\Auth\Password\ForgotPasswordForm;
use App\Mail\Auth\PasswordLinkMail;
use App\Models\Admin;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * A forgotten password is replaced through a link for an hour · and the answer
 * never says whether an address is an account's.
 */
final class ForgottenPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const string ANSWER = 'Si cette adresse est celle d’un compte, un lien vient d’y partir.';

    private Admin $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://chouchoute-toi.fr']);
        Mail::fake();

        // The message leaves after the answer · run at once here, and on its own in the test that says so.
        $this->withoutDefer();

        // The time every answer takes · a millisecond here, the shipped one in the test that measures it.
        config(['auth.timebox_duration' => 1000]);

        $this->owner = Admin::factory()->create(['email' => 'amandine@exemple.fr']);
    }

    public function test_the_sign_in_page_leads_to_it(): void
    {
        $this->get(route('auth.login'))->assertSee(route('auth.password.forgot'));
        $this->get(route('auth.password.forgot'))->assertOk()->assertSee('Recevoir le lien');
    }

    public function test_a_known_address_receives_a_link_for_an_hour(): void
    {
        $this->aRequestFor('amandine@exemple.fr')->assertSet('sent', true)->assertSee(self::ANSWER);

        Mail::assertSent(PasswordLinkMail::class, fn (PasswordLinkMail $mail): bool => $mail->hasTo('amandine@exemple.fr')
            && str_starts_with($mail->link, 'https://chouchoute-toi.fr/admin/mot-de-passe/'.$this->owner->id.'/')
            && $mail->minutes === 60);
    }

    public function test_the_screen_and_the_message_say_the_time_the_configuration_gives(): void
    {
        config(['auth.passwords.admins.expire' => 30]);

        $this->aRequestFor('amandine@exemple.fr')->assertSee('Il est valable 30 minutes.');

        Mail::assertSent(PasswordLinkMail::class, static fn (PasswordLinkMail $mail): bool => $mail->minutes === 30);
    }

    public function test_an_empty_or_malformed_address_says_what_to_write(): void
    {
        $this->aRequestFor('')->assertHasErrors(['email' => 'Indiquez votre adresse e-mail.']);
        $this->aRequestFor('amandine')->assertHasErrors(['email' => 'Indiquez une adresse e-mail valide.']);
    }

    public function test_an_unknown_address_gets_the_same_answer_and_nothing_leaves(): void
    {
        $this->aRequestFor('personne@exemple.fr')->assertSet('sent', true)->assertSee(self::ANSWER);

        Mail::assertNothingSent();
    }

    public function test_a_suspended_account_receives_nothing(): void
    {
        $this->owner->forceFill(['suspended_at' => CarbonImmutable::now()])->save();

        $this->aRequestFor('amandine@exemple.fr')->assertSee(self::ANSWER);

        Mail::assertNothingSent();
    }

    public function test_a_second_link_within_a_minute_does_not_leave(): void
    {
        $this->aRequestFor('amandine@exemple.fr');
        $this->aRequestFor('amandine@exemple.fr')->assertSee(self::ANSWER);

        Mail::assertSentCount(1);
    }

    public function test_asking_too_often_waits(): void
    {
        foreach (['un@exemple.fr', 'deux@exemple.fr', 'trois@exemple.fr'] as $address) {
            $this->aRequestFor($address)->assertHasNoErrors();
        }

        $this->aRequestFor('amandine@exemple.fr')->assertHasErrors('email')->assertSee('Trop de demandes.');

        Mail::assertNothingSent();
    }

    /** The answer leaves before the message · an address that opens an account answers no later than one that does not. */
    public function test_the_message_leaves_after_the_answer(): void
    {
        $this->withDefer();

        $this->aRequestFor('amandine@exemple.fr')->assertSet('sent', true)->assertSee(self::ANSWER);

        Mail::assertNothingSent();

        app(DeferredCallbackCollection::class)->invoke();

        Mail::assertSent(PasswordLinkMail::class, static fn (PasswordLinkMail $mail): bool => $mail->hasTo('amandine@exemple.fr'));
    }

    /** Both answers take the same time at least, above what hashing a new link costs. */
    public function test_a_known_and_an_unknown_address_answer_after_the_same_time(): void
    {
        $shipped = (require base_path('config/auth.php'))['timebox_duration'];
        $this->assertSame(500000, $shipped);
        config(['auth.timebox_duration' => $shipped]);

        foreach (['amandine@exemple.fr', 'personne@exemple.fr'] as $address) {
            $started = hrtime(true);
            $this->aRequestFor($address);

            $this->assertGreaterThanOrEqual(0.5, (hrtime(true) - $started) / 1e9, $address);
        }
    }

    public function test_a_failure_while_sending_is_written_down_and_says_nothing_more(): void
    {
        $log = Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('The relay refused the message.'));

        $this->aRequestFor('amandine@exemple.fr')->assertSet('sent', true);

        $log->shouldHaveReceived('error');
    }

    public function test_the_link_chooses_a_new_password_and_signs_in(): void
    {
        $this->aRequestFor('amandine@exemple.fr');
        $link = (string) Mail::sent(PasswordLinkMail::class)->map(static fn (PasswordLinkMail $mail): string => $mail->link)->first();
        $this->assertSame(1, preg_match('#/admin/mot-de-passe/(\d+)/([^/]+)$#', $link, $parts));

        $password = bin2hex(random_bytes(8));

        Livewire::test(ChoosePasswordForm::class, ['account' => (int) $parts[1], 'token' => $parts[2], 'purpose' => 'reset'])
            ->set('password', $password)
            ->set('passwordConfirmation', $password)
            ->call('choose')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(Hash::check($password, (string) $this->owner->fresh()?->password));
    }

    public function test_the_message_reads_as_a_message_of_the_site(): void
    {
        $html = (new PasswordLinkMail('Amandine', 'https://chouchoute-toi.fr/admin/mot-de-passe/1/abc', 60))->render();

        $this->assertStringContainsString('href="https://chouchoute-toi.fr/admin/mot-de-passe/1/abc"', $html);
        $this->assertStringContainsString('valable 60 minutes', $html);
        $this->assertStringNotContainsString('—', $html);
    }

    private function aRequestFor(string $address): Testable
    {
        return Livewire::test(ForgotPasswordForm::class)->set('email', $address)->call('send');
    }
}
