<?php

declare(strict_types=1);

namespace App\Services\Auth\Password;

use App\Mail\Auth\PasswordLinkMail;
use App\Models\Admin;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The forgotten password · a link to choose a new one, sent when the address is
 * an account's, and nothing to say whether it is.
 */
final readonly class PasswordResetService
{
    public function __construct(private PasswordLinksService $links) {}

    /** Sends a link to choose a new password, when this address is an account's. */
    public function sendTheLinkTo(string $email): void
    {
        try {
            $this->links->links(PasswordLinksService::PASSWORD_RESETS)->sendResetLink(['email' => $email], function (CanResetPassword $account, string $token): void {
                if (! $account instanceof Admin) {
                    return;
                }

                Mail::to($account->email)->send(new PasswordLinkMail(
                    $account->name,
                    $this->links->urlFor('auth.password.reset', $account, $token),
                    $this->links->minutesOf(PasswordLinksService::PASSWORD_RESETS),
                ));
            });
        } catch (Throwable $failure) {
            Log::error('Sending a password link failed.', ['exception' => $failure]);
        }
    }
}
