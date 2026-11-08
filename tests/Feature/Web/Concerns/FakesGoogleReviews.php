<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Concerns;

use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A place Google can be asked about, its answer, and the snapshot kept from it ·
 * shared by the tests of the reviews page.
 */
trait FakesGoogleReviews
{
    /** A key and a place, and no snapshot until a case lays one. */
    private function aPlaceGoogleKnows(): void
    {
        config([
            'services.google.api_key' => bin2hex(random_bytes(8)),
            'services.google.place_id' => 'a-place',
            'avis' => ['captured_on' => null, 'rating' => null, 'total' => null, 'reviews' => []],
        ]);
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
     * @return array<string, mixed>
     */
    private function googleAnswer(): array
    {
        return [
            'rating' => 4.9,
            'userRatingCount' => 31,
            'googleMapsLinks' => [
                'reviewsUri' => 'https://www.google.com/maps/place/a-place/reviews',
                'writeAReviewUri' => 'https://www.google.com/maps/place/a-place/write',
            ],
            'reviews' => [[
                'rating' => 5,
                'authorAttribution' => [
                    'displayName' => 'Inès Fauvel',
                    'uri' => 'https://www.google.com/maps/contrib/ines',
                    'photoUri' => 'https://lh3.example.test/ines.png',
                ],
                'text' => ['text' => 'Un accueil très soigné.'],
                'relativePublishTimeDescription' => 'il y a 2 mois',
                'publishTime' => '2026-09-14T09:30:00Z',
                'googleMapsUri' => 'https://www.google.com/maps/reviews/ines',
                'visitDate' => ['year' => 2026, 'month' => 9],
            ]],
        ];
    }

    private function aSnapshot(): void
    {
        config(['avis' => [
            'captured_on' => '2026-11-03',
            'rating' => 4.8,
            'total' => 27,
            'all_reviews' => 'https://www.google.com/maps/place/a-place/reviews',
            'write_a_review' => 'https://www.google.com/maps/place/a-place/write',
            'reviews' => [[
                'nom' => 'Léa Moreau',
                'note' => 5,
                'texte' => 'Toujours aussi précise.',
                'date' => 'octobre 2026',
                'publie_le' => '2026-10-12',
                'photo' => 'https://lh3.example.test/lea.png',
                'profil' => 'https://www.google.com/maps/contrib/lea',
                'lien' => 'https://www.google.com/maps/reviews/lea',
                'visite' => 'octobre 2026',
            ]],
        ]]);
    }
}
