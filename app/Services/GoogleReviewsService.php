<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @phpstan-type Review array{nom: string, note: int, texte: string, date: string, publie_le: string|null, photo: string|null, profil: string|null, lien: string|null, visite: string|null}
 * @phpstan-type Reviews array{reviews: list<Review>, rating: float|null, total: int|null, all_reviews: string|null, write_a_review: string|null}
 */
class GoogleReviewsService
{
    /** Calls the site makes in a day before the snapshot is shown instead. */
    public const int CALLS_PER_DAY = 30;

    /** Reviews Google gives at most for a place. */
    public const int MOST_REVIEWS = 5;

    /** Where a visit keeps what Google answered it, in the shape that credits the authors. */
    private const string VISIT_KEY = 'google-reviews.answer';

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
        $allReviews = config('avis.all_reviews');
        $writeAReview = config('avis.write_a_review');

        return [
            'reviews' => $reviews,
            'rating' => is_numeric($rating) ? (float) $rating : null,
            'total' => is_int($total) ? $total : null,
            'all_reviews' => is_string($allReviews) ? $allReviews : null,
            'write_a_review' => is_string($writeAReview) ? $writeAReview : null,
        ];
    }

    /** How many reviews the page waits for · as many as the snapshot says Google gives, at most five. */
    public function expectedReviews(): int
    {
        $snapshot = $this->snapshot();

        return min(self::MOST_REVIEWS, $snapshot['total'] ?? self::MOST_REVIEWS);
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
                'X-Goog-FieldMask' => 'reviews,rating,userRatingCount,googleMapsLinks',
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

            $reviews = array_map($this->reviewFrom(...), $raw);

            if ($reviews === []) {
                return null;
            }

            return [
                'reviews' => $reviews,
                'rating' => is_numeric($data['rating'] ?? null) ? (float) $data['rating'] : null,
                'total' => is_numeric($data['userRatingCount'] ?? null) ? (int) $data['userRatingCount'] : null,
                'all_reviews' => $this->textOf($data['googleMapsLinks']['reviewsUri'] ?? null),
                'write_a_review' => $this->textOf($data['googleMapsLinks']['writeAReviewUri'] ?? null),
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Reviews fetch failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * One review as the page shows it · its author credited, the review and the
     * visit dated, as Google asks of whoever shows its reviews.
     *
     * @param  array<string, mixed>  $review
     * @return Review
     */
    private function reviewFrom(array $review): array
    {
        /** @var array<string, mixed> $author */
        $author = is_array($review['authorAttribution'] ?? null) ? $review['authorAttribution'] : [];
        /** @var array<string, mixed> $text */
        $text = is_array($review['text'] ?? null) ? $review['text'] : [];
        $visit = $review['visitDate'] ?? null;

        return [
            'nom' => $this->textOf($author['displayName'] ?? null) ?? 'Cliente',
            'note' => is_numeric($review['rating'] ?? null) ? (int) $review['rating'] : 5,
            'texte' => $this->textOf($text['text'] ?? null) ?? '',
            'date' => $this->textOf($review['relativePublishTimeDescription'] ?? null) ?? '',
            'publie_le' => is_string($review['publishTime'] ?? null)
                ? Carbon::parse($review['publishTime'])->timezone(config()->string('app.timezone'))->toDateString()
                : null,
            'photo' => $this->textOf($author['photoUri'] ?? null),
            'profil' => $this->textOf($author['uri'] ?? null),
            'lien' => $this->textOf($review['googleMapsUri'] ?? null),
            'visite' => is_array($visit) && is_numeric($visit['year'] ?? null) && is_numeric($visit['month'] ?? null)
                ? Carbon::createFromDate((int) $visit['year'], (int) $visit['month'], 1)->translatedFormat('F Y')
                : null,
        ];
    }

    /** A text field of Google's answer, or null when it is missing or empty. */
    private function textOf(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
