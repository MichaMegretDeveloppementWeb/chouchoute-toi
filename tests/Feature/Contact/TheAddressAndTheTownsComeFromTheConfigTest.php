<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Services\BusinessContactService;
use Tests\TestCase;

/**
 * The postal address and the towns served live in `config/entreprise.php`
 * alone · every list and the legal notice read them there.
 */
final class TheAddressAndTheTownsComeFromTheConfigTest extends TestCase
{
    private const CHIP = '<span class="rounded-full bg-sand px-4 py-2 text-sm font-medium text-dark">%s</span>';

    /** @var list<string> */
    private array $configuredTowns = [];

    private string $configuredAddress = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuredTowns = BusinessContactService::townsServed();
        $this->configuredAddress = BusinessContactService::address();

        config([
            'entreprise.villes_desservies' => ['Orbec', 'Vimoutiers'],
            'entreprise.adresse' => [
                'rue' => '14 rue des Glycines',
                'code_postal' => '32160',
                'ville' => 'Plaisance',
                'region' => 'Gers',
                'pays' => 'FR',
            ],
        ]);
    }

    public function test_the_home_page_lists_the_configured_towns(): void
    {
        $page = $this->get(route('home'))->assertOk();

        $page->assertSeeInOrder([sprintf(self::CHIP, 'Orbec'), sprintf(self::CHIP, 'Vimoutiers')], false);

        foreach ($this->configuredTowns as $town) {
            $page->assertDontSee(sprintf(self::CHIP, $town), false);
        }
    }

    public function test_the_contact_page_lists_the_configured_towns_twice(): void
    {
        $page = $this->get(route('contact'))->assertOk();

        $page->assertSeeInOrder([sprintf(self::CHIP, 'Orbec'), sprintf(self::CHIP, 'Vimoutiers')], false)
            ->assertSeeInOrder(['<option value="Orbec">Orbec</option>', '<option value="Vimoutiers">Vimoutiers</option>', '<option value="Autre">Autre</option>'], false);

        foreach ($this->configuredTowns as $town) {
            $page->assertDontSee(sprintf(self::CHIP, $town), false)
                ->assertDontSee('<option value="'.$town.'">', false);
        }
    }

    public function test_the_legal_notice_gives_the_configured_address_and_towns(): void
    {
        $this->get(route('legal'))
            ->assertOk()
            ->assertSee('14 rue des Glycines, 32160 Plaisance')
            ->assertDontSee($this->configuredAddress)
            ->assertSee('couvrant Orbec, Vimoutiers')
            ->assertDontSee('couvrant '.implode(', ', $this->configuredTowns));
    }
}
