<?php

declare(strict_types=1);

namespace App\Services;

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
    /** Calls one visitor's address may make in an hour before the snapshot is shown instead. */
    public const int CALLS_PER_VISITOR_PER_HOUR = 6;

    /** Calls the site makes in a day before the snapshot is shown instead. */
    public const int CALLS_PER_DAY = 30;

    private const int REQUEST_SECONDS = 2;

    /**
     * The real reviews of the configured Place ID, asked of Google for this
     * visitor · the snapshot when there is no key, when a limit is reached, or
     * when Google gives nothing.
     *
     * @return Reviews
     */
    public function liveReviews(string $visitor): array
    {
        $apiKey = config('services.google.api_key');
        $placeId = config('services.google.place_id');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($placeId) || $placeId === '') {
            return $this->snapshot();
        }

        if (! $this->mayAskGoogle($visitor)) {
            return $this->snapshot();
        }

        return $this->askGoogle($apiKey, $placeId) ?? $this->snapshot();
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

    /** Counts one call against both limits, or says one of them is reached. */
    private function mayAskGoogle(string $visitor): bool
    {
        $today = 'google-reviews:day:'.now()->toDateString();
        $visitorHour = 'google-reviews:visitor:'.$visitor;

        if (RateLimiter::tooManyAttempts($today, self::CALLS_PER_DAY)
            || RateLimiter::tooManyAttempts($visitorHour, self::CALLS_PER_VISITOR_PER_HOUR)) {
            return false;
        }

        RateLimiter::hit($today, 86400);
        RateLimiter::hit($visitorHour, 3600);

        return true;
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
