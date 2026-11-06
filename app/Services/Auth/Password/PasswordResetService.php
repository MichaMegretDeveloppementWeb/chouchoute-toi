<?php

declare(strict_types=1);

namespace App\Services\Auth\Password;

use App\Mail\Auth\PasswordLinkMail;
use App\Models\Admin;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

/**
 * The forgotten password · a link to choose a new one, sent when the address is
 * an account's, and nothing to say whether it is · the answer takes the same
 * time either way, and the message leaves after it.
 */
final readonly class PasswordResetService
{
    public function __construct(private PasswordLinksService $links) {}

    /** Prepares a link to choose a new password when this address is an account's, sent once the answer has left. */
    public function sendTheLinkTo(string $email): void
    {
        try {
            $this->links->links(PasswordLinksService::PASSWORD_RESETS)->sendResetLink(['email' => $email], function (CanResetPassword $account, string $token): void {
                if (! $account instanceof Admin) {
                    return;
                }

                $mail = new PasswordLinkMail(
                    $account->name,
                    $this->links->urlFor('auth.password.reset', $account, $token),
                    $this->links->minutesOf(PasswordLinksService::PASSWORD_RESETS),
                );

                defer(static fn () => self::send($account, $mail));
            });
        } catch (Throwable $failure) {
            Log::error('Preparing a password link failed.', ['exception' => $failure]);
        }
    }

    /** Sends the link, its failure written to the log · nobody waits for it any more. */
    private static function send(Admin $account, PasswordLinkMail $mail): void
    {
        try {
            Mail::to($account->email)->send($mail);
        } catch (Throwable $failure) {
            Log::error('Sending a password link failed.', ['admin_id' => $account->id, 'exception' => $failure]);
        }
    }
}
