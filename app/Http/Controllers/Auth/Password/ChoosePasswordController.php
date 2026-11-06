<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Password;

use App\Http\Controllers\Controller;
use App\Services\Auth\SignIn\SignInService;
use Illuminate\View\View;

/**
 * The page a link opens, an invitation or a forgotten password, to choose a
 * password · or, for an account already signed in, who it is and the way out.
 */
final class ChoosePasswordController extends Controller
{
    public function __invoke(int $account, string $token, string $purpose, SignInService $signIn): View
    {
        $signedIn = $signIn->signedIn();

        if ($signedIn !== null) {
            return view('auth.link-while-signed-in', [
                'name' => $signedIn->name,
                'account' => $account,
                'token' => $token,
                'purpose' => $purpose,
            ]);
        }

        return view('auth.choose-password', [
            'account' => $account,
            'token' => $token,
            'purpose' => $purpose,
        ]);
    }
}
