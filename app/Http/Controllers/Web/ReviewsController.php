<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoogleReviewsService;
use Illuminate\View\View;

class ReviewsController extends Controller
{
    public function __invoke(GoogleReviewsService $reviewsService): View
    {
        $googleData = $reviewsService->getReviews();

        return view('web.reviews.index', [
            'googleReviews' => $googleData['reviews'],
            'googleRating' => $googleData['rating'],
            'googleTotal' => $googleData['total'],
        ]);
    }
}
