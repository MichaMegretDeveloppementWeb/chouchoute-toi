<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The booking legal notice names the site's web host, as the law requires
 * and as the site's own legal notice does, in the same words.
 */
final class TheLegalNoticeNamesTheHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_booking_legal_notice_names_the_host_as_the_site_does(): void
    {
        $booking = $this->get(route('booking.public.legal'))->assertOk();
        $site = $this->get(route('legal'))->assertOk();

        foreach (['Hostinger International Ltd', '61 Lordou Vironos Street, 6023 Larnaca, Chypre', '+370 645 03378'] as $line) {
            $booking->assertSee($line);
            $site->assertSee($line);
        }
    }
}
