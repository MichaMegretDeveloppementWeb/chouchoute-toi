<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use Falcon\Analytics\Events\EventRegistry;
use Falcon\Analytics\Funnels\FunnelRegistry;
use Tests\TestCase;

/**
 * The site's declarations load, and the scores they carry are whole points.
 *
 * The analytics screens draw their figures lazily, so rendering them reads
 * neither declaration file. And a file the package cannot load is only written
 * to the log: without this test, a refused score would show as conversions
 * missing from the screens, and nothing else.
 */
final class TheSiteDeclaresWholeScoresTest extends TestCase
{
    public function test_the_declared_events_load_with_their_scores(): void
    {
        $events = app(EventRegistry::class);

        $this->assertCount(7, $events->all());
        $this->assertSame(80, $events->get('contact.request.submitted')?->value);
        $this->assertSame(1, $events->get('social.facebook.click')?->value);
    }

    public function test_the_declared_funnels_load_with_their_scores(): void
    {
        $funnels = app(FunnelRegistry::class);

        $this->assertCount(2, $funnels->all());
        $this->assertSame([2, 8, 80], array_map(
            fn ($step): int => $step->value,
            $funnels->get('demande_rdv')?->steps() ?? [],
        ));
    }
}
