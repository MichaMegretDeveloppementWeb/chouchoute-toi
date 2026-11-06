<?php

declare(strict_types=1);

namespace App\Services\Auth\SignIn;

use App\Models\Admin;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;

/**
 * Signing in to the back-office, the session renewed each time so that the one
 * opened before cannot be taken over.
 */
final readonly class SignInService
{
    /** Signs in with an address and a password · null when they open no account. */
    public function attempt(string $email, string $password, bool $remember): ?Admin
    {
        if (! Auth::guard('admin')->attempt(['email' => $email, 'password' => $password], $remember)) {
            return null;
        }

        $this->renewTheSession();

        $account = Auth::guard('admin')->user();

        return $account instanceof Admin ? $account : null;
    }

    /** Signs this account in, as a link that has just chosen its password does. */
    public function signIn(Admin $account): void
    {
        Auth::guard('admin')->login($account);

        $this->renewTheSession();
    }

    /** The address an account arrives on once signed in. */
    public function homeOf(Admin $account): string
    {
        return route($account->role->home());
    }

    private function renewTheSession(): void
    {
        // Through the container, the reactive test harness carrying no session store on its request.
        app(Store::class)->regenerate();
    }
}
