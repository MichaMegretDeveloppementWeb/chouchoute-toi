<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The reviews page only says what real clients wrote · Google's answer, or the
 * snapshot taken from it · and shows no section when it has neither.
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

    public function test_google_s_reviews_and_rating_are_shown(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee(self::SECTION)
            ->assertSee('Inès Fauvel')
            ->assertSee('Un accueil très soigné.')
            ->assertSee('il y a 2 mois')
            ->assertSee('4,9/5 sur Google')
            ->assertSee('(31 avis)');
    }

    public function test_the_request_says_what_it_needs_and_does_not_wait_for_long(): void
    {
        $waits = null;

        Http::fake(function (Request $request, array $options) use (&$waits) {
            $waits = $options['timeout'] ?? null;

            return Http::response($this->googleAnswer());
        });

        $this->get(route('reviews'))->assertOk();

        $this->assertSame(5, $waits);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1/places/a-place')
            && $request->hasHeader('X-Goog-FieldMask', 'reviews,rating,userRatingCount'));
    }

    public function test_a_refusal_shows_the_snapshot_and_nothing_invented(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response(['error' => ['status' => 'PERMISSION_DENIED']], 403)]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee('Léa Moreau')
            ->assertSee('Toujours aussi précise.')
            ->assertSee('octobre 2026')
            ->assertSee('4,8/5 sur Google')
            ->assertDontSee('Sophie M.')
            ->assertDontSee('exemple');
    }

    public function test_a_refusal_without_a_snapshot_draws_no_section(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response([], 403)]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertDontSee(self::SECTION)
            ->assertDontSee('Sophie M.')
            ->assertDontSee('exemple')
            ->assertSee('Rejoindre mes clientes satisfaites');
    }

    public function test_a_connection_that_fails_shows_the_snapshot(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::failedConnection()]);

        $this->get(route('reviews'))->assertOk()->assertSee('Léa Moreau');
    }

    public function test_an_answer_without_reviews_shows_the_snapshot(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response(['rating' => 4.9, 'userRatingCount' => 31])]);

        $this->get(route('reviews'))->assertOk()->assertSee('Léa Moreau');
    }

    public function test_a_refusal_is_remembered_for_a_quarter_of_an_hour_and_google_is_then_asked_again(): void
    {
        $this->aSnapshot();
        Http::fakeSequence('places.googleapis.com/*')
            ->push([], 403)
            ->push($this->googleAnswer());

        $this->get(route('reviews'))->assertSee('Léa Moreau');
        $this->travel(14)->minutes();
        $this->get(route('reviews'))->assertSee('Léa Moreau');
        Http::assertSentCount(1);

        $this->travel(2)->minutes();
        $this->get(route('reviews'))->assertSee('Inès Fauvel')->assertDontSee('Léa Moreau');
        Http::assertSentCount(2);
    }

    public function test_an_answer_is_remembered_for_a_day(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleAnswer())]);

        $this->get(route('reviews'))->assertSee('Inès Fauvel');
        $this->travel(23)->hours();
        $this->get(route('reviews'))->assertSee('Inès Fauvel');
        Http::assertSentCount(1);

        $this->travel(2)->hours();
        $this->get(route('reviews'))->assertSee('Inès Fauvel');
        Http::assertSentCount(2);
    }

    public function test_without_a_key_nothing_is_asked_and_the_snapshot_is_shown(): void
    {
        $this->aSnapshot();
        config(['services.google.api_key' => '']);
        Http::fake();

        $this->get(route('reviews'))->assertOk()->assertSee('Léa Moreau');

        Http::assertNothingSent();
    }

    public function test_the_structured_data_says_what_is_shown(): void
    {
        $this->aSnapshot();
        Http::fake(['places.googleapis.com/*' => Http::response([], 403)]);

        $this->get(route('reviews'))
            ->assertSee('"ratingValue":"4.8"', false)
            ->assertSee('"ratingCount":"27"', false)
            ->assertSee('"name":"Léa Moreau"', false);
    }

    public function test_the_structured_data_says_nothing_when_nothing_is_shown(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response([], 403)]);

        $this->get(route('reviews'))
            ->assertDontSee('"aggregateRating"', false)
            ->assertDontSee('"reviewBody"', false);
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
            ]],
        ]]);
    }
}
