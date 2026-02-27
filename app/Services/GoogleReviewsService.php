<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleReviewsService
{
    /**
     * Récupère les avis Google pour le Place ID configuré.
     * Les résultats sont mis en cache pour 24h.
     *
     * @return array{reviews: array<int, array{nom: string, note: int, texte: string, date: string}>, rating: float|null, total: int|null}
     */
    public function getReviews(): array
    {
        $apiKey = config('services.google.api_key');
        $placeId = config('services.google.place_id');

        if (! $apiKey || ! $placeId) {
            return ['reviews' => [], 'rating' => null, 'total' => null];
        }

        return Cache::remember('google_reviews', now()->addDay(), function () use ($apiKey, $placeId) {
            try {
                $response = Http::withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => 'reviews,rating,userRatingCount',
                ])->get("https://places.googleapis.com/v1/places/{$placeId}", [
                    'languageCode' => 'fr',
                ]);

                if ($response->failed()) {
                    Log::warning('Google Places API error', ['status' => $response->status()]);

                    return ['reviews' => [], 'rating' => null, 'total' => null];
                }

                $data = $response->json();

                $reviews = collect($data['reviews'] ?? [])
                    ->filter(fn (array $review) => ($review['rating'] ?? 0) >= 4)
                    ->map(fn (array $review) => [
                        'nom' => $review['authorAttribution']['displayName'] ?? 'Cliente',
                        'note' => (int) ($review['rating'] ?? 5),
                        'texte' => $review['text']['text'] ?? '',
                        'date' => $review['relativePublishTimeDescription'] ?? '',
                    ])
                    ->values()
                    ->all();

                return [
                    'reviews' => $reviews,
                    'rating' => $data['rating'] ?? null,
                    'total' => $data['userRatingCount'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::warning('Google Reviews fetch failed', ['error' => $e->getMessage()]);

                return ['reviews' => [], 'rating' => null, 'total' => null];
            }
        });
    }
}
