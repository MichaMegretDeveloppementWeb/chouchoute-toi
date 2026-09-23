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
 *
 * `@analyticsCollector` lays the collector and its configuration on a public
 * page, and lays nothing while an administrator is signed in.
 */
final class TrackingExclusionTest extends TestCase
{
    use RefreshDatabase;

    /** Ce que la directive pose, et ce que le collecteur lit pour démarrer. */
    private const CONFIG = 'window.__falconAnalytics=';

    public function test_collector_is_configured_for_an_anonymous_visitor(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(self::CONFIG, escape: false);
    }

    public function test_collector_is_not_configured_while_an_admin_is_signed_in(): void
    {
        $response = $this->actingAs(Admin::factory()->create(), 'admin')->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(self::CONFIG, escape: false);
    }

    public function test_collector_is_still_configured_for_a_client_signed_in_on_the_web_guard(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'web')->get(route('home'));

        $response->assertOk();
        $response->assertSee(self::CONFIG, escape: false);
    }

    /** The collector's former address answers nothing · the package serves it as a published file. */
    public function test_the_collector_script_is_no_longer_served_by_the_package(): void
    {
        $this->get('/__analytics.js')->assertNotFound();
    }

    /**
     * A public page receives the collector once · the package's file, and the
     * site's own script carries none. A second copy counts every click twice.
     */
    public function test_a_public_page_receives_the_collector_once(): void
    {
        $page = (string) $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($page, 'vendor/falcon/analytics/analytics.js'));

        /** @var array<string, array{file: string}> $manifest */
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $siteScript = (string) file_get_contents(public_path('build/'.$manifest['resources/js/web.js']['file']));

        $this->assertStringNotContainsString('__falconAnalytics', $siteScript);
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
