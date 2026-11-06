<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\Auth\AddressChangedMail;
use App\Mail\Auth\InvitationMail;
use App\Mail\Auth\PasswordLinkMail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The three messages of the sign-in space leave from the sender the site sets,
 * and name the site as that sender does · one setting for all three.
 */
final class TheMessagesAreSignedByTheSenderTest extends TestCase
{
    /** @return array<string, array{0: InvitationMail|PasswordLinkMail|AddressChangedMail}> */
    public static function messages(): array
    {
        return [
            'the invitation' => [new InvitationMail('Julie', 'julie@exemple.fr', 'https://exemple.fr/admin/bienvenue/1/abc', 7)],
            'the forgotten password' => [new PasswordLinkMail('Julie', 'https://exemple.fr/admin/mot-de-passe/1/abc', 60)],
            'the changed address' => [new AddressChangedMail('Julie', 'julie@exemple.fr')],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.from.name' => 'Institut Exemple', 'mail.from.address' => 'bonjour@exemple.fr']);
    }

    #[DataProvider('messages')]
    public function test_it_names_the_site_as_the_sender_does(InvitationMail|PasswordLinkMail|AddressChangedMail $message): void
    {
        $html = $message->render();

        $this->assertStringContainsString('l’administration de Institut Exemple', $html);
        $this->assertStringNotContainsString('Chouchoute', $html);
    }

    #[DataProvider('messages')]
    public function test_it_signs_with_no_sender_of_its_own(InvitationMail|PasswordLinkMail|AddressChangedMail $message): void
    {
        $this->assertNull($message->envelope()->from);
    }

    public function test_the_invitation_names_the_site_in_its_subject(): void
    {
        $invitation = new InvitationMail('Julie', 'julie@exemple.fr', 'https://exemple.fr/admin/bienvenue/1/abc', 7);

        $this->assertSame('Votre accès à l’administration de Institut Exemple', $invitation->envelope()->subject);
    }
}
