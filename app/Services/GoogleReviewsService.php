<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @phpstan-type Review array{nom: string, note: int, texte: string, date: string, publie_le: string|null}
 * @phpstan-type Reviews array{reviews: list<Review>, rating: float|null, total: int|null}
 */
class GoogleReviewsService
{
    /** Calls the site makes in a day before the snapshot is shown instead. */
    public const int CALLS_PER_DAY = 30;

    /** Where a visit keeps what Google answered it. */
    private const string VISIT_KEY = 'google-reviews';

    private const int REQUEST_SECONDS = 2;

    /**
     * The real reviews of the configured Place ID · Google's answer, asked once
     * per visit and kept for it · the snapshot when there is no key, when the
     * calls of the day are spent, or when Google gives nothing.
     *
     * @return Reviews
     */
    public function liveReviews(Session $visit): array
    {
        $kept = $visit->get(self::VISIT_KEY);

        if (is_array($kept)) {
            /** @var Reviews $kept */
            return $kept;
        }

        $apiKey = config('services.google.api_key');
        $placeId = config('services.google.place_id');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($placeId) || $placeId === '') {
            return $this->snapshot();
        }

        if (! $this->spendACallOfTheDay()) {
            return $this->snapshot();
        }

        $answer = $this->askGoogle($apiKey, $placeId);

        if ($answer === null) {
            return $this->snapshot();
        }

        $visit->put(self::VISIT_KEY, $answer);

        return $answer;
    }

    /**
     * The copy of real reviews kept in `config/avis.php`.
     *
     * @return Reviews
     */
    public function snapshot(): array
    {
        /** @var list<Review> $reviews */
        $reviews = config()->array('avis.reviews', []);
        $rating = config('avis.rating');
        $total = config('avis.total');

        return [
            'reviews' => $reviews,
            'rating' => is_numeric($rating) ? (float) $rating : null,
            'total' => is_int($total) ? $total : null,
        ];
    }

    /** Spends one call of the day, and says whether the day still allowed it. */
    private function spendACallOfTheDay(): bool
    {
        return RateLimiter::hit('google-reviews:day:'.now()->toDateString(), 86400) <= self::CALLS_PER_DAY;
    }

    /**
     * What Google answers, or null when it refuses, fails or gives no review.
     *
     * @return Reviews|null
     */
    private function askGoogle(string $apiKey, string $placeId): ?array
    {
        try {
            $response = Http::timeout(self::REQUEST_SECONDS)->withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'reviews,rating,userRatingCount',
            ])->get("https://places.googleapis.com/v1/places/{$placeId}", [
                'languageCode' => 'fr',
            ]);

            if ($response->failed()) {
                Log::warning('Google Places API error', ['status' => $response->status(), 'reason' => $response->json('error.status')]);

                return null;
            }

            $data = $response->json();

            // Nobody promises what the API returns: every field is read
            // with its default, and the shape is declared so what follows
            // types instead of drifting into mixed.
            /** @var list<array<string, mixed>> $raw */
            $raw = is_array($data['reviews'] ?? null) ? array_values($data['reviews']) : [];

            /** @var list<Review> $reviews */
            $reviews = collect($raw)
                ->filter(fn (array $review): bool => ((int) ($review['rating'] ?? 0)) >= 4)
                ->map(fn (array $review) => [
                    'nom' => $review['authorAttribution']['displayName'] ?? 'Cliente',
                    'note' => (int) ($review['rating'] ?? 5),
                    'texte' => $review['text']['text'] ?? '',
                    'date' => $review['relativePublishTimeDescription'] ?? '',
                    'publie_le' => is_string($review['publishTime'] ?? null)
                        ? Carbon::parse($review['publishTime'])->timezone(config()->string('app.timezone'))->toDateString()
                        : null,
                ])
                ->values()
                ->all();

            if ($reviews === []) {
                return null;
            }

            return [
                'reviews' => $reviews,
                'rating' => is_numeric($data['rating'] ?? null) ? (float) $data['rating'] : null,
                'total' => is_numeric($data['userRatingCount'] ?? null) ? (int) $data['userRatingCount'] : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Reviews fetch failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
