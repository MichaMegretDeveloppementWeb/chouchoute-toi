<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Models\Admin;
use App\Models\User;
use Falcon\Analytics\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        $this->send($this->batch());

        $this->assertSame(1, Event::query()->count());
    }

    public function test_ingestion_stores_nothing_while_an_admin_is_signed_in(): void
    {
        $batch = $this->batch();

        $this->actingAs(Admin::factory()->create(), 'admin')->send($batch);

        $this->assertSame(0, Event::query()->count());
    }

    public function test_ingestion_stores_the_event_of_a_client_signed_in_on_the_web_guard(): void
    {
        $batch = $this->batch();

        $this->actingAs(User::factory()->create(), 'web')->send($batch);

        $this->assertSame(1, Event::query()->count());
    }

    /**
     * Posts the batch as a browser does · from the site's origin, the facts
     * written once the response has left.
     *
     * @param  array<string, mixed>  $batch
     */
    private function send(array $batch): void
    {
        $this->withoutDefer()
            ->withHeader('Origin', config('app.url'))
            ->postJson('/__analytics', $batch)
            ->assertNoContent();
    }

    /**
     * A batch as the collector sends it · under the envelope a public page
     * gives it, each event with an identifier of its own.
     *
     * @return array{envelope: string, sent_at: int, referrer: null, events: list<array<string, mixed>>}
     */
    private function batch(): array
    {
        $page = (string) $this->get(route('home'))->assertOk()->getContent();
        preg_match('/'.preg_quote(self::CONFIG, '/').'(\{.*?\});/', $page, $found);
        /** @var array{envelope?: string} $config */
        $config = json_decode($found[1] ?? '{}', true);
        $this->assertArrayHasKey('envelope', $config, 'A public page gives the collector its envelope.');

        $sentAt = (int) (microtime(true) * 1000);

        return [
            'envelope' => $config['envelope'],
            'sent_at' => $sentAt,
            'referrer' => null,
            'events' => [
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'pageview',
                    'ts' => $sentAt,
                    'route' => 'home',
                    'url' => 'http://localhost/',
                ],
            ],
        ];
    }
}
