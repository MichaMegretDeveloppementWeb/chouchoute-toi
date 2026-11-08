<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoogleReviewsService;
use Illuminate\View\View;

/** The reviews page, drawn at once · its reviews arrive from `ReviewsListController`. */
class ReviewsController extends Controller
{
    public function __invoke(GoogleReviewsService $reviewsService): View
    {
        $snapshot = $reviewsService->snapshot();

        return view('web.reviews.index', [
            'snapshotReviews' => $snapshot['reviews'],
            'snapshotRating' => $snapshot['rating'],
            'snapshotTotal' => $snapshot['total'],
            'expectedReviews' => $reviewsService->expectedReviews(),
        ]);
    }
}
