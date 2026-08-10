<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Models\Admin;
use App\Models\User;
use Falcon\Analytics\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Project invariant: every public visit is measured, back-office traffic never
 * is. The exclusion goes through the `admin` guard alone, never through a
 * global switch that would also blind the public site.
 */
final class TrackingExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_is_rendered_for_an_anonymous_visitor(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('__analytics.js', escape: false);
    }

    public function test_collector_is_not_rendered_while_an_admin_is_signed_in(): void
    {
        $response = $this->actingAs(Admin::factory()->create(), 'admin')->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('__analytics.js', escape: false);
    }

    public function test_collector_is_still_rendered_for_a_client_signed_in_on_the_web_guard(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'web')->get(route('home'));

        $response->assertOk();
        $response->assertSee('__analytics.js', escape: false);
    }

    public function test_ingestion_stores_an_event_sent_by_a_public_visitor(): void
    {
        $this->postJson('/__analytics', $this->batch())->assertNoContent();

        $this->assertSame(1, Event::query()->count());
    }

    public function test_ingestion_stores_nothing_while_an_admin_is_signed_in(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->postJson('/__analytics', $this->batch())
            ->assertNoContent();

        $this->assertSame(0, Event::query()->count());
    }

    public function test_ingestion_stores_the_event_of_a_client_signed_in_on_the_web_guard(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->postJson('/__analytics', $this->batch())
            ->assertNoContent();

        $this->assertSame(1, Event::query()->count());
    }

    /**
     * @return array{sent_at: int, referrer: null, events: array<int, array<string, mixed>>}
     */
    private function batch(): array
    {
        $sentAt = (int) (microtime(true) * 1000);

        return [
            'sent_at' => $sentAt,
            'referrer' => null,
            'events' => [
                [
                    'type' => 'pageview',
                    'ts' => $sentAt,
                    'route' => 'home',
                    'url' => 'http://localhost/',
                ],
            ],
        ];
    }
}
