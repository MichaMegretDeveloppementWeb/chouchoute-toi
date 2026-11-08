<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoogleReviewsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The testimonials section of the reviews page, served on its own once the page is drawn. */
class ReviewsListController extends Controller
{
    /** A section read live, which no cache keeps and no engine indexes. */
    private const array HEADERS = [
        'Cache-Control' => 'no-store, private',
        'X-Robots-Tag' => 'noindex',
    ];

    public function __invoke(Request $request, GoogleReviewsService $reviewsService): Response
    {
        $answer = $reviewsService->liveReviews($request->session());

        if ($answer['reviews'] === []) {
            return response()->noContent()->withHeaders(self::HEADERS);
        }

        return response()->view('web.reviews.partials.temoignages', [
            'reviews' => $answer['reviews'],
            'rating' => $answer['rating'],
            'total' => $answer['total'],
            'allReviews' => $answer['all_reviews'],
            'writeAReview' => $answer['write_a_review'],
            'expected' => count($answer['reviews']),
        ])->withHeaders(self::HEADERS);
    }
}
