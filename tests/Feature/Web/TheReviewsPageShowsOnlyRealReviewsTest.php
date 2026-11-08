<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Services\GoogleReviewsService;
use App\Services\SiteGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * The reviews page only says what real clients wrote · it is drawn at once
 * without asking Google, and its testimonials arrive on their own · Google's
 * answer, asked at each visit within two limits, or the snapshot taken from it.
 */
final class TheReviewsPageShowsOnlyRealReviewsTest extends TestCase
{
    use RefreshDatabase;

    private const string SECTION = 'Ce que disent mes clientes';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.api_key' => bin2hex(random_bytes(8)),
            'services.google.place_id' => 'a-place',
            'avis' => ['captured_on' => null, 'rating' => null, 'total' => null, 'reviews' => []],
        ]);
    }

    public function test_the_page_asks_google_nothing_and_waits_for_its_reviews(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee(self::SECTION)
            ->assertSee('data-reviews-source="'.route('reviews.list').'"', false)
            ->assertSee('aria-busy="true"', false)
            ->assertSee('Chargement des avis')
            ->assertSee('Les avis ne se chargent pas pour l’instant.')
            ->assertDontSee('Inès Fauvel');

        Http::assertNothingSent();
    }

    public function test_google_s_reviews_and_rating_are_sent_with_their_section(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list()
            ->assertOk()
            ->assertSee(self::SECTION)
            ->assertSee('Inès Fauvel')
            ->assertSee('Un accueil très soigné.')
            ->assertSee('il y a 2 mois')
            ->assertSee('4,9/5 sur Google')
            ->assertSee('(31 avis)')
            ->assertSee('data-accordion-item', false)
            ->assertDontSee('data-reviews-source', false)
            ->assertDontSee('Chargement des avis');
    }

    public function test_the_section_comes_alone_neither_kept_nor_indexed(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $sent = $this->list();

        $this->assertStringStartsWith('<section', ltrim((string) $sent->getContent()));
        $this->assertStringEndsWith('</section>', rtrim((string) $sent->getContent()));
        $sent->assertDontSee('<html', false)->assertDontSee('<script', false);
        $this->assertNeitherKeptNorIndexed($sent);
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
            && $request->hasHeader('X-Goog-FieldMask', 'reviews,rating,userRatingCount'));
    }

    public function test_each_visit_asks_google_again(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list()->assertSee('Inès Fauvel');
        $this->list()->assertSee('Inès Fauvel');

        Http::assertSentCount(2);
    }

    public function test_a_refusal_shows_the_snapshot_and_nothing_invented(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response(['error' => ['status' => 'PERMISSION_DENIED']], 403)]);

        $this->list()
            ->assertOk()
            ->assertSee('Léa Moreau')
            ->assertSee('Toujours aussi précise.')
            ->assertSee('octobre 2026')
            ->assertSee('4,8/5 sur Google')
            ->assertDontSee('Sophie M.')
            ->assertDontSee('exemple');
    }

    public function test_a_refusal_without_a_snapshot_sends_no_section(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response([], 403)]);

        $sent = $this->list()->assertNoContent();

        $this->assertNeitherKeptNorIndexed($sent);
        $this->get(route('reviews'))->assertOk()->assertSee('Rejoindre mes clientes satisfaites');
    }

    public function test_a_connection_that_fails_shows_the_snapshot(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::failedConnection()]);

        $this->list()->assertOk()->assertSee('Léa Moreau');
    }

    public function test_an_answer_without_reviews_shows_the_snapshot(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response(['rating' => 4.9, 'userRatingCount' => 31])]);

        $this->list()->assertOk()->assertSee('Léa Moreau');
    }

    public function test_without_a_key_nothing_is_asked_and_the_snapshot_is_shown(): void
    {
        $this->aSnapshot();
        config(['services.google.api_key' => '']);
        Http::fake();

        $this->list()->assertOk()->assertSee('Léa Moreau');

        Http::assertNothingSent();
    }

    public function test_one_visitor_is_shown_the_snapshot_beyond_its_calls_of_the_hour(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        for ($call = 0; $call < GoogleReviewsService::CALLS_PER_VISITOR_PER_HOUR; $call++) {
            $this->list('203.0.113.7')->assertSee('Inès Fauvel');
        }

        $this->list('203.0.113.7')->assertSee('Léa Moreau')->assertDontSee('Inès Fauvel');
        $this->list('203.0.113.8')->assertSee('Inès Fauvel');
        Http::assertSentCount(GoogleReviewsService::CALLS_PER_VISITOR_PER_HOUR + 1);

        $this->travel(61)->minutes();

        $this->list('203.0.113.7')->assertSee('Inès Fauvel');
        Http::assertSentCount(GoogleReviewsService::CALLS_PER_VISITOR_PER_HOUR + 2);
    }

    public function test_the_site_is_shown_the_snapshot_beyond_its_calls_of_the_day(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);
        $this->travelTo(now()->startOfDay()->addHours(9));

        for ($call = 0; $call < GoogleReviewsService::CALLS_PER_DAY; $call++) {
            $this->list('203.0.113.'.($call + 1))->assertSee('Inès Fauvel');
        }

        $this->list('198.51.100.1')->assertSee('Léa Moreau')->assertDontSee('Inès Fauvel');
        Http::assertSentCount(GoogleReviewsService::CALLS_PER_DAY);

        $this->travelTo(now()->addDay()->startOfDay()->addMinute());

        $this->list('198.51.100.1')->assertSee('Inès Fauvel');
        Http::assertSentCount(GoogleReviewsService::CALLS_PER_DAY + 1);
    }

    public function test_the_structured_data_says_what_the_snapshot_holds(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $reviewed = $this->reviewedNode();
        $named = ['@type' => 'BeautySalon', '@id' => SiteGraphService::id('business'), 'name' => config('entreprise.nom')];

        $this->assertSame('4.8', $reviewed['aggregateRating']['ratingValue']);
        $this->assertSame('27', $reviewed['aggregateRating']['ratingCount']);
        $this->assertSame($named, $reviewed['aggregateRating']['itemReviewed']);

        $review = $reviewed['review'][0];

        $this->assertSame('Review', $review['@type']);
        $this->assertSame(['@type' => 'Person', 'name' => 'Léa Moreau'], $review['author']);
        $this->assertSame('5', $review['reviewRating']['ratingValue']);
        $this->assertSame('Toujours aussi précise.', $review['reviewBody']);
        $this->assertSame('2026-10-12', $review['datePublished']);
        $this->assertSame($named, $review['itemReviewed']);
        $this->assertCount(1, $reviewed['review']);
        Http::assertNothingSent();
    }

    public function test_the_structured_data_says_nothing_without_a_snapshot(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->get(route('reviews'))
            ->assertDontSee('"aggregateRating"', false)
            ->assertDontSee('"reviewBody"', false);
    }

    public function test_a_review_read_from_google_says_when_it_was_published(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->assertSame('2026-09-14', app(GoogleReviewsService::class)->liveReviews('203.0.113.7')['reviews'][0]['publie_le']);
    }

    public function test_a_review_without_a_known_date_says_none(): void
    {
        $answer = $this->googleAnswer();
        unset($answer['reviews'][0]['publishTime']);

        Http::fake(['places.googleapis.com/*' => Http::response($answer)]);

        $this->assertNull(app(GoogleReviewsService::class)->liveReviews('203.0.113.7')['reviews'][0]['publie_le']);
    }

    public function test_the_snapshot_kept_is_made_of_real_answers(): void
    {
        /** @var array{captured_on: string, rating: float, total: int, reviews: list<array{nom: string, note: int, texte: string, date: string, publie_le: string}>} $snapshot */
        $snapshot = require base_path('config/avis.php');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $snapshot['captured_on']);
        $this->assertGreaterThan(0, $snapshot['total']);
        $this->assertNotEmpty($snapshot['reviews']);

        foreach ($snapshot['reviews'] as $review) {
            $this->assertNotSame('', $review['nom']);
            $this->assertNotSame('', $review['texte']);
            $this->assertNotSame('', $review['date']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $review['publie_le']);
            $this->assertGreaterThanOrEqual(4, $review['note']);
            $this->assertLessThanOrEqual(5, $review['note']);
        }

        $names = array_column($snapshot['reviews'], 'nom');

        foreach (['Sophie M.', 'Marie L.', 'Camille D.', 'Julie R.', 'Léa B.', 'Nathalie P.'] as $invented) {
            $this->assertNotContains($invented, $names);
        }
    }

    /**
     * The section as the page's script asks for it, from one visitor's address.
     *
     * @return TestResponse<Response>
     */
    private function list(string $visitor = '127.0.0.1'): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $visitor])->get(route('reviews.list'));
    }

    /**
     * @param  TestResponse<Response>  $sent
     */
    private function assertNeitherKeptNorIndexed(TestResponse $sent): void
    {
        $this->assertStringContainsString('no-store', (string) $sent->headers->get('Cache-Control'));
        $this->assertSame('noindex', $sent->headers->get('X-Robots-Tag'));
    }

    /**
     * The node of the graph that carries the reviews, read as a search engine
     * reads the page.
     *
     * @return array<string, mixed>
     */
    private function reviewedNode(): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', (string) $this->get(route('reviews'))->getContent(), $blocks);

        foreach ($blocks[1] as $block) {
            foreach (json_decode($block, true, flags: JSON_THROW_ON_ERROR)['@graph'] as $node) {
                if (isset($node['review'])) {
                    return $node;
                }
            }
        }

        $this->fail('No node of the graph carries the reviews.');
    }

    /**
     * @return array<string, mixed>
     */
    private function googleAnswer(): array
    {
        return [
            'rating' => 4.9,
            'userRatingCount' => 31,
            'reviews' => [[
                'rating' => 5,
                'authorAttribution' => ['displayName' => 'Inès Fauvel'],
                'text' => ['text' => 'Un accueil très soigné.'],
                'relativePublishTimeDescription' => 'il y a 2 mois',
                'publishTime' => '2026-09-14T09:30:00Z',
            ]],
        ];
    }

    private function aSnapshot(): void
    {
        config(['avis' => [
            'captured_on' => '2026-11-03',
            'rating' => 4.8,
            'total' => 27,
            'reviews' => [[
                'nom' => 'Léa Moreau',
                'note' => 5,
                'texte' => 'Toujours aussi précise.',
                'date' => 'octobre 2026',
                'publie_le' => '2026-10-12',
            ]],
        ]]);
    }
}
