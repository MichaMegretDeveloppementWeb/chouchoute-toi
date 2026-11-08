<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Services\GoogleReviewsService;
use Illuminate\Cache\RateLimiter as Limiter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Feature\Web\Concerns\FakesGoogleReviews;
use Tests\TestCase;

/**
 * Google is asked for the reviews at the first load of a visit, and its answer is
 * kept for the visit · within a daily limit, past which the snapshot is shown.
 */
final class GoogleIsAskedOncePerVisitTest extends TestCase
{
    use FakesGoogleReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aPlaceGoogleKnows();
    }

    public function test_the_request_says_what_it_needs_and_does_not_wait_for_long(): void
    {
        $waits = null;

        Http::fake(function (Request $request, array $options) use (&$waits) {
            $waits = $options['timeout'] ?? null;

            return Http::response($this->googleAnswer());
        });

        $this->list()->assertOk();

        $this->assertSame(2, $waits);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1/places/a-place')
            && $request->hasHeader('X-Goog-FieldMask', 'reviews,rating,userRatingCount,googleMapsLinks'));
    }

    public function test_each_visit_asks_google_again(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list()->assertSee('Inès Fauvel');
        $this->flushSession();
        $this->list()->assertSee('Inès Fauvel');

        Http::assertSentCount(2);
    }

    public function test_a_second_load_of_the_same_visit_asks_google_nothing(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list()->assertSee('Inès Fauvel');
        $this->list()->assertSee('Inès Fauvel')->assertSee('31 avis sur Google');

        Http::assertSentCount(1);
    }

    public function test_a_visit_shown_the_snapshot_asks_again_at_its_next_load(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['status' => 'UNAVAILABLE']], 503)
            ->push($this->googleAnswer())]);

        $this->list()->assertSee('Léa Moreau');
        $this->list()->assertSee('Inès Fauvel');

        Http::assertSentCount(2);
    }

    public function test_counting_the_calls_keeps_no_address_of_the_visitor(): void
    {
        config(['cache.default' => 'database']);
        $this->app->forgetInstance(Limiter::class);
        RateLimiter::clearResolvedInstance(Limiter::class);
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list('203.0.113.7')->assertSee('Inès Fauvel');

        $this->assertGreaterThan(0, DB::table('cache')->count(), 'The calls of the day are not counted in the store.');
        $this->assertSame(0, DB::table('cache')->where('key', 'like', '%203.0.113.7%')->count());
    }

    public function test_the_site_is_shown_the_snapshot_beyond_its_calls_of_the_day(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);
        $this->travelTo(now()->startOfDay()->addHours(9));

        for ($visit = 0; $visit < GoogleReviewsService::CALLS_PER_DAY; $visit++) {
            $this->flushSession();
            $this->list()->assertSee('Inès Fauvel');
        }

        $this->flushSession();
        $this->list()->assertSee('Léa Moreau')->assertDontSee('Inès Fauvel');
        Http::assertSentCount(GoogleReviewsService::CALLS_PER_DAY);

        $this->travelTo(now()->addDay()->startOfDay()->addMinute());

        $this->flushSession();
        $this->list()->assertSee('Inès Fauvel');
        Http::assertSentCount(GoogleReviewsService::CALLS_PER_DAY + 1);
    }

    public function test_a_review_read_from_google_says_when_it_was_published(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->assertSame('2026-09-14', app(GoogleReviewsService::class)->liveReviews(app(Session::class))['reviews'][0]['publie_le']);
    }

    public function test_a_review_without_a_known_date_says_none(): void
    {
        $answer = $this->googleAnswer();
        unset($answer['reviews'][0]['publishTime']);

        Http::fake(['places.googleapis.com/*' => Http::response($answer)]);

        $this->assertNull(app(GoogleReviewsService::class)->liveReviews(app(Session::class))['reviews'][0]['publie_le']);
    }
}
