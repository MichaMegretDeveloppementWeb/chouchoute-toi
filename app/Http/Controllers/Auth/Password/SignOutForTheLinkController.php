<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Password;

use App\Http\Controllers\Controller;
use App\Services\Auth\SignIn\SignInService;
use Illuminate\Http\RedirectResponse;

/**
 * Signs the account out, then opens the link it could not open while signed in.
 */
final class SignOutForTheLinkController extends Controller
{
    public function __invoke(string $purpose, int $account, string $token, SignInService $signIn): RedirectResponse
    {
        $signIn->signOut();

        return redirect()->route($purpose === 'invitation' ? 'auth.invitation' : 'auth.password.reset', [
            'account' => $account,
            'token' => $token,
        ]);
    }
}
