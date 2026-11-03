<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @phpstan-type Review array{nom: string, note: int, texte: string, date: string}
 * @phpstan-type Reviews array{reviews: list<Review>, rating: float|null, total: int|null}
 */
class GoogleReviewsService
{
    private const string CACHE_KEY = 'google_reviews.answer';

    private const int REQUEST_SECONDS = 5;

    /**
     * The real reviews of the configured Place ID · Google's answer, kept for a
     * day, or the snapshot taken from it, Google being asked again after a
     * quarter of an hour.
     *
     * @return Reviews
     */
    public function getReviews(): array
    {
        $apiKey = config('services.google.api_key');
        $placeId = config('services.google.place_id');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($placeId) || $placeId === '') {
            return $this->snapshot();
        }

        $kept = Cache::get(self::CACHE_KEY);

        if (is_array($kept)) {
            /** @var Reviews $kept */
            return $kept;
        }

        $answer = $this->askGoogle($apiKey, $placeId);

        if ($answer === null) {
            $snapshot = $this->snapshot();

            Cache::put(self::CACHE_KEY, $snapshot, now()->addMinutes(15));

            return $snapshot;
        }

        Cache::put(self::CACHE_KEY, $answer, now()->addDay());

        return $answer;
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

    /**
     * The copy of real reviews kept in `config/avis.php`.
     *
     * @return Reviews
     */
    private function snapshot(): array
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
}
