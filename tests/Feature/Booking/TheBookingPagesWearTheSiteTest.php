<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use Falcon\Booking\Models\Client;
use Falcon\Booking\Services\Shared\Client\ClientLinkService;
use Falcon\Booking\ValueObjects\Client\SpaceScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * The booking pages are pages of the site · its header and footer around the
 * package's screen, the package's styles before ours so that ours win, the
 * reactive runtime written out, and our colours on what the package opens.
 */
final class TheBookingPagesWearTheSiteTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<Response> */
    private function page(): TestResponse
    {
        return $this->get(route('booking.public.home'))->assertOk();
    }

    public function test_the_page_has_the_site_header_and_footer(): void
    {
        $this->wearsTheSite($this->page());
    }

    public function test_the_page_to_find_ones_appointments_wears_the_site_too(): void
    {
        $this->wearsTheSite($this->get(route('booking.public.space.find'))->assertOk())
            ->assertSee('Retrouver mes rendez-vous');
    }

    public function test_the_legal_notice_wears_the_site_too(): void
    {
        $this->wearsTheSite($this->get(route('booking.public.legal'))->assertOk())
            ->assertSee('Mentions légales');
    }

    /** A short page · the body is a screen high at least, and our footer stays at its foot. */
    public function test_the_missing_page_wears_the_site_and_keeps_the_footer_down(): void
    {
        $this->wearsTheSite($this->get('/reservation/nulle-part')->assertNotFound())
            ->assertSee('Cette page n’existe pas ou plus.')
            ->assertSee('<body class="flex min-h-dvh flex-col', false);
    }

    public function test_the_clients_space_wears_the_site_too(): void
    {
        $links = app(ClientLinkService::class);
        $link = $links->urlFor($links->issueFor(Client::factory()->create(), SpaceScope::whole()));

        $this->assertNotNull($link);

        $this->wearsTheSite($this->followingRedirects()->get($link)->assertOk())
            ->assertSee('Vos rendez-vous');
    }

    /**
     * The site's header and footer around the package's screen, and not the package's own shell.
     *
     * @param  TestResponse<Response>  $response
     * @return TestResponse<Response>
     */
    private function wearsTheSite(TestResponse $response): TestResponse
    {
        return $response
            ->assertSee('data-header', false)
            ->assertSee('<main class="booking-page flex-1">', false)
            ->assertSee('class="bg-black text-white"', false)
            ->assertDontSee('fb-public-shell', false);
    }

    /** The order of the sheets decides who wins · the package's first, then ours. */
    public function test_the_package_styles_come_before_the_sites(): void
    {
        $html = (string) $this->page()->getContent();

        $package = strpos($html, 'vendor/falcon/booking/booking.css');
        $site = strpos($html, '/build/assets/web-');

        $this->assertNotFalse($package, 'The package stylesheet is missing.');
        $this->assertNotFalse($site, 'The site stylesheet is missing.');
        $this->assertLessThan($site, $package);
    }

    /**
     * The kit's sheet is the first the page reads, before the font's too · the
     * order of the layers is fixed by the first sheet, and the kit's check
     * cannot tell a font sheet from one that declares layers.
     */
    public function test_the_kit_comes_before_any_other_sheet(): void
    {
        $head = explode('</head>', (string) $this->page()->getContent())[0];

        preg_match_all('/<link\b[^>]*\brel=["\']stylesheet["\'][^>]*>/i', $head, $sheets);

        $this->assertGreaterThan(2, count($sheets[0]), 'The page carries the kit, the font and our own sheet at least.');
        $this->assertStringContainsString('vendor/falcon/ui/', $sheets[0][0], 'Another sheet comes before the kit: '.$sheets[0][0]);
    }

    /** Without the runtime written out, the reservation renders whole and answers no click. */
    public function test_the_reactive_runtime_and_the_public_script_are_there(): void
    {
        $this->page()
            ->assertSee('livewire', false)
            ->assertSee('vendor/falcon/booking/booking-public.js', false)
            ->assertDontSee('booking-admin.js', false);
    }

    /** Our colours on the variables the package opens, nowhere else. */
    public function test_our_colours_dress_the_package(): void
    {
        $sheet = (string) file_get_contents(resource_path('css/web/booking.css'));

        $this->assertMatchesRegularExpression('/\.fb-public\s*\{[^}]*--fb-public-pick:\s*#512731/', $sheet);
        $this->assertStringContainsString("@import './web/booking.css';", (string) file_get_contents(resource_path('css/web.css')));
        $this->assertSame('layout.booking', config('booking.layouts.public'));
    }

    /** A price keeps the package's weight, and an icon the grey of the mentions · neither is ours to colour. */
    public function test_prices_and_icons_stay_quiet(): void
    {
        $sheet = (string) file_get_contents(resource_path('css/web/booking.css'));

        $this->assertStringNotContainsString('--fb-public-figure-weight', $sheet);
        $this->assertStringNotContainsString('--fb-public-icon', $sheet);
    }
}
