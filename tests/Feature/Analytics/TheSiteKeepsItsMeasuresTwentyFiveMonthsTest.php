<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use Tests\TestCase;

/**
 * The site keeps its sessions, its visitor profiles and its named events
 * twenty-five months, the CNIL's ceiling for an audience measurement run
 * without a consent banner, and its page-by-page journeys ninety days.
 *
 * Durations that contradict each other are refused by the package, which then
 * erases nothing at all · the second test holds the two rules it applies.
 */
final class TheSiteKeepsItsMeasuresTwentyFiveMonthsTest extends TestCase
{
    public function test_the_sessions_and_the_named_events_are_kept_twenty_five_months(): void
    {
        $this->assertSame(90, config('analytics.retention_days'));
        $this->assertSame(760, config('analytics.session_retention_days'));
        $this->assertSame(760, config('analytics.event_retention_days'));
    }

    public function test_the_three_durations_do_not_contradict_each_other(): void
    {
        $pages = config('analytics.retention_days');
        $sessions = config('analytics.session_retention_days');
        $events = config('analytics.event_retention_days');

        $this->assertIsInt($pages);
        $this->assertIsInt($sessions);
        $this->assertIsInt($events);

        $this->assertGreaterThanOrEqual($pages, $sessions, 'Erasing a session erases its page views.');
        $this->assertLessThanOrEqual($sessions, $events, 'A named event leaves with its session.');
    }
}
