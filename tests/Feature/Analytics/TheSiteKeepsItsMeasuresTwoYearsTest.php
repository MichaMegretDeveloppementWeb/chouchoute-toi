<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use Tests\TestCase;

/**
 * The screens offer seven, thirty and ninety days and the last twelve months.
 * The package keeps its detail twice the longest period, so the site keeps
 * two years, within the CNIL's twenty-five months for an audience measurement
 * run without a consent banner.
 *
 * The durations the package no longer reads are ignored without a word · the
 * last test keeps them from coming back.
 */
final class TheSiteKeepsItsMeasuresTwoYearsTest extends TestCase
{
    /** Twenty-five months are never fewer days than this. */
    private const int CNIL_CEILING_DAYS = 758;

    public function test_the_screens_offer_four_periods_and_open_on_thirty_days(): void
    {
        $this->assertSame(
            [7 => null, 30 => null, 90 => null, 365 => '12 derniers mois'],
            config('analytics.periods'),
        );
        $this->assertSame(30, config('analytics.default_period'));
    }

    public function test_twice_the_longest_period_stays_within_the_cnil_ceiling(): void
    {
        $periods = array_keys((array) config('analytics.periods'));

        $this->assertNotContains('all', $periods, 'The whole history would keep every session forever.');
        $this->assertContainsOnlyInt($periods);
        $kept = 2 * max([0, ...array_map(intval(...), $periods)]);

        $this->assertSame(730, $kept);
        $this->assertLessThanOrEqual(self::CNIL_CEILING_DAYS, $kept);
    }

    public function test_the_durations_the_package_no_longer_reads_are_gone(): void
    {
        foreach (['retention_days', 'session_retention_days', 'event_retention_days'] as $key) {
            $this->assertFalse(config()->has("analytics.{$key}"), "analytics.{$key} is ignored by the package.");
        }
    }
}
