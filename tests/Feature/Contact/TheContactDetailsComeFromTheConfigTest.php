<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Services\BusinessContactService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The email address and the phone number live in `config/entreprise.php`
 * alone · every page that shows them reads them there.
 */
final class TheContactDetailsComeFromTheConfigTest extends TestCase
{
    public function test_a_french_number_is_shown_as_people_read_it(): void
    {
        $this->assertSame('06 39 98 12 34', BusinessContactService::phoneForDisplay('+33639981234'));
    }

    /** @return array<string, array{string}> */
    public static function publicPages(): array
    {
        return [
            'home' => ['home'],
            'about' => ['about'],
            'reviews' => ['reviews'],
            'treatments' => ['prestations'],
            'contact' => ['contact'],
            'legal notice' => ['legal'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_a_page_shows_the_configured_contact_details_and_no_other(string $page): void
    {
        $email = (string) config('entreprise.email');
        $phone = (string) config('entreprise.telephone');

        config(['entreprise.email' => 'contact@exemple.fr', 'entreprise.telephone' => '+33639981234']);

        $this->get(route($page))
            ->assertOk()
            ->assertSee('mailto:contact@exemple.fr', false)
            ->assertSee('tel:+33639981234', false)
            ->assertSee('06 39 98 12 34', false)
            ->assertDontSee($email, false)
            ->assertDontSee($phone, false)
            ->assertDontSee(BusinessContactService::phoneForDisplay($phone), false);
    }
}
