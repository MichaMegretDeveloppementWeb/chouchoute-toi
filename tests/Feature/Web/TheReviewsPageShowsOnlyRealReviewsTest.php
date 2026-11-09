<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Services\SiteGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Web\Concerns\FakesGoogleReviews;
use Tests\TestCase;

/**
 * The reviews page only says what real clients wrote, as Google asks them to be
 * shown · it is drawn at once without asking Google, and its testimonials arrive
 * on their own · Google's answer, or the snapshot taken from it.
 */
final class TheReviewsPageShowsOnlyRealReviewsTest extends TestCase
{
    use FakesGoogleReviews;
    use RefreshDatabase;

    private const string SECTION = 'Ce que disent mes clientes';

    protected function setUp(): void
    {
        parent::setUp();

        $this->aPlaceGoogleKnows();
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
            ->assertSee('4,9')
            ->assertSee('31 avis sur Google')
            ->assertSee('data-accordion-item', false)
            ->assertDontSee('data-reviews-source', false)
            ->assertDontSee('Chargement des avis');
    }

    public function test_each_review_credits_its_author_and_leads_to_google(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list()
            ->assertSee('src="https://lh3.example.test/ines.png"', false)
            ->assertSee('href="https://www.google.com/maps/contrib/ines"', false)
            ->assertSee('Profil de Inès Fauvel')
            ->assertSee('href="https://www.google.com/maps/reviews/ines"', false)
            ->assertSee('Voir l’avis sur Google Maps')
            ->assertSee('Visité en septembre 2026')
            ->assertSee('Les plus pertinents selon Google Maps, sans tri ni filtre de notre part.');
    }

    public function test_the_summary_leads_to_every_review_and_to_writing_one(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->list()
            ->assertSee('href="https://www.google.com/maps/place/a-place/reviews"', false)
            ->assertSee('Voir tous les avis')
            ->assertSee('href="https://www.google.com/maps/place/a-place/write"', false)
            ->assertSee('Laisser un avis');
    }

    public function test_a_review_under_four_stars_is_shown_too(): void
    {
        $answer = $this->googleAnswer();
        $answer['reviews'][] = ['rating' => 2, 'authorAttribution' => ['displayName' => 'Odile Marchal'], 'text' => ['text' => 'Pas convaincue.']];

        Http::fake(['places.googleapis.com/*' => Http::response($answer)]);

        $this->list()->assertSee('Inès Fauvel')->assertSee('Odile Marchal')->assertSee('Pas convaincue.');
    }

    public function test_a_review_given_without_words_shows_no_empty_quote(): void
    {
        $answer = $this->googleAnswer();
        $answer['reviews'][] = ['rating' => 4, 'authorAttribution' => ['displayName' => 'Hugo Lenoir']];

        Http::fake(['places.googleapis.com/*' => Http::response($answer)]);

        $section = (string) $this->list()->assertSee('Hugo Lenoir')->getContent();

        $this->assertSame(1, substr_count($section, '«'));
    }

    public function test_the_summary_leads_to_google_even_without_a_rating(): void
    {
        $answer = $this->googleAnswer();
        unset($answer['rating']);

        Http::fake(['places.googleapis.com/*' => Http::response($answer)]);

        $this->list()->assertSee('Voir tous les avis')->assertSee('Laisser un avis');
    }

    public function test_the_waiting_rows_are_as_many_as_the_reviews_expected(): void
    {
        $this->aSnapshot();
        config(['avis.total' => 3]);

        $page = (string) $this->get(route('reviews'))->assertOk()->getContent();

        $this->assertSame(3, substr_count($page, 'data-review-placeholder'));
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

    public function test_a_refusal_shows_the_snapshot_and_nothing_invented(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response(['error' => ['status' => 'PERMISSION_DENIED']], 403)]);

        $this->list()
            ->assertOk()
            ->assertSee('Léa Moreau')
            ->assertSee('Toujours aussi précise.')
            ->assertSee('octobre 2026')
            ->assertSee('4,8')->assertSee('27 avis sur Google')
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

    public function test_the_snapshot_kept_is_made_of_real_answers(): void
    {
        /** @var array{captured_on: string, rating: float, total: int, all_reviews: string, write_a_review: string, reviews: list<array{nom: string, note: int, texte: string, date: string, publie_le: string, photo: string, profil: string, lien: string, visite: string}>} $snapshot */
        $snapshot = require base_path('config/avis.php');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $snapshot['captured_on']);
        $this->assertGreaterThan(0, $snapshot['total']);
        $this->assertStringStartsWith('https://', $snapshot['all_reviews']);
        $this->assertStringStartsWith('https://', $snapshot['write_a_review']);
        $this->assertNotEmpty($snapshot['reviews']);

        foreach ($snapshot['reviews'] as $review) {
            $this->assertNotSame('', $review['nom']);
            $this->assertNotSame('', $review['texte']);
            $this->assertNotSame('', $review['date']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $review['publie_le']);
            $this->assertGreaterThanOrEqual(1, $review['note']);
            $this->assertLessThanOrEqual(5, $review['note']);
            $this->assertStringStartsWith('https://', $review['photo']);
            $this->assertStringStartsWith('https://', $review['profil']);
            $this->assertStringStartsWith('https://', $review['lien']);
            $this->assertMatchesRegularExpression('/^\p{Ll}+ \d{4}$/u', $review['visite']);
        }

        $names = array_column($snapshot['reviews'], 'nom');

        foreach (['Sophie M.', 'Marie L.', 'Camille D.', 'Julie R.', 'Léa B.', 'Nathalie P.'] as $invented) {
            $this->assertNotContains($invented, $names);
        }
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
}
