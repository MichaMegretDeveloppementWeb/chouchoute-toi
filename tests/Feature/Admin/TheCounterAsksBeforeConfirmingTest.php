<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * An appointment entered by hand opens with its confirmation unticked on this
 * site · the establishment confirms by phone, and ticks the box when the
 * client wants the message.
 */
final class TheCounterAsksBeforeConfirmingTest extends TestCase
{
    public function test_the_confirmation_of_a_counter_booking_starts_unticked(): void
    {
        $this->assertFalse(config('booking.settings.notifications.confirm_counter_bookings'));
    }
}
