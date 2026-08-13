<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Livewire\ContactForm;
use App\Mail\ContactMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The contact request is the site's only conversion until the booking feature
 * ships: a request that never reaches Amandine is a lost client.
 *
 * Note on the analytics side: send() also records the `contact.request.submitted`
 * conversion server-side. That call is deliberately not asserted here. The
 * package resolves the visitor identity from the session, and Livewire::test()
 * runs outside the `web` middleware group, so no session is bound to the
 * request and the recorder bails out. Both the recorder and the Analytics
 * manager are `final readonly`, so neither can be mocked. Asserting it here
 * would only produce a test that passes for the wrong reason.
 */
final class SendContactRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_the_contact_page_is_reachable(): void
    {
        $this->get(route('contact'))->assertOk();
    }

    public function test_a_complete_request_is_mailed(): void
    {
        $this->fillForm()->call('send')->assertHasNoErrors();

        Mail::assertSent(ContactMail::class, fn (ContactMail $mail): bool => $mail->hasTo('dc.amandine@gmail.com'));
    }

    public function test_the_form_is_emptied_and_confirms_after_sending(): void
    {
        $this->fillForm()
            ->call('send')
            ->assertSet('sent', true)
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('phone', '');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideRequiredFields(): array
    {
        return [
            'nom' => ['name', ''],
            'email manquant' => ['email', ''],
            'email invalide' => ['email', 'pas-une-adresse'],
            'telephone' => ['phone', ''],
        ];
    }

    #[DataProvider('provideRequiredFields')]
    public function test_an_incomplete_request_is_rejected_and_never_mailed(string $field, string $value): void
    {
        $this->fillForm()
            ->set($field, $value)
            ->call('send')
            ->assertHasErrors($field)
            ->assertSet('sent', false);

        Mail::assertNothingSent();
    }

    public function test_the_volume_from_the_pricing_link_preselects_the_offer(): void
    {
        Livewire::withQueryParams(['volume' => 'volume-mixte'])
            ->test(ContactForm::class)
            ->assertSet('volume', 'volume-mixte');
    }

    public function test_an_unknown_volume_in_the_url_is_ignored(): void
    {
        Livewire::withQueryParams(['volume' => 'nimporte-quoi'])
            ->test(ContactForm::class)
            ->assertSet('volume', '');
    }

    public function test_changing_the_volume_resets_the_chosen_prestation(): void
    {
        $this->fillForm()
            ->set('volume', 'naturelle')
            ->set('prestation', 'pose-complete')
            ->set('volume', 'volume-intense')
            ->assertSet('prestation', '');
    }

    private function fillForm(): Testable
    {
        return Livewire::test(ContactForm::class)
            ->set('name', 'Camille Durand')
            ->set('email', 'camille@example.test')
            ->set('phone', '0600000000')
            ->set('commune', 'Évian-les-Bains');
    }
}
