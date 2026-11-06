<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\SignIn;

use App\Http\Controllers\Controller;
use App\Services\Auth\SignIn\SignInService;
use Illuminate\Http\RedirectResponse;

final class LogoutController extends Controller
{
    public function __invoke(SignInService $signIn): RedirectResponse
    {
        $signIn->signOut();

        return redirect()->route('auth.login');
    }
}
