<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * An appointment entered by hand opens with « Prévenir le client » unticked
 * by e-mail on this site · the establishment confirms by phone, and ticks the
 * box when the client wants the message. What follows a visit is still told.
 */
final class TheCounterAsksBeforeConfirmingTest extends TestCase
{
    public function test_a_counter_booking_opens_with_its_email_unticked(): void
    {
        $this->assertFalse(config('booking.settings.notifications.client.admin.booked.email'));
    }

    public function test_what_follows_a_visit_is_still_told_by_email(): void
    {
        $this->assertTrue(config('booking.settings.notifications.client.admin.changed.email'));
        $this->assertTrue(config('booking.settings.notifications.client.admin.cancelled.email'));
    }

    public function test_the_removed_switch_is_gone(): void
    {
        $this->assertNull(config('booking.settings.notifications.confirm_counter_bookings'));
    }
}
