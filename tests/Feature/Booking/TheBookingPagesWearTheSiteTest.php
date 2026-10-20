<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The booking pages are pages of the site · its header and footer around the
 * package's screen, the package's styles before ours so that ours win, the
 * reactive runtime written out, and our colours on what the package opens.
 */
final class TheBookingPagesWearTheSiteTest extends TestCase
{
    use RefreshDatabase;

    private function page(): TestResponse
    {
        return $this->get(route('booking.public.home'))->assertOk();
    }

    public function test_the_page_has_the_site_header_and_footer(): void
    {
        $this->page()
            ->assertSee('data-header', false)
            ->assertSee('<main class="booking-page">', false)
            ->assertSee('class="bg-black text-white"', false)
            ->assertDontSee('fb-public-shell', false);
    }

    /** The order of the sheets decides who wins · the package's first, then ours. */
    public function test_the_package_styles_come_before_the_sites(): void
    {
        $html = $this->page()->getContent();

        $package = strpos($html, 'vendor/falcon/booking/booking.css');
        $site = strpos($html, '/build/assets/web-');

        $this->assertNotFalse($package, 'The package stylesheet is missing.');
        $this->assertNotFalse($site, 'The site stylesheet is missing.');
        $this->assertLessThan($site, $package);
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
}
