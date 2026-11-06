<?php

declare(strict_types=1);

namespace App\Services\Auth\Account;

use App\Mail\Auth\AddressChangedMail;
use App\Mail\Auth\InvitationMail;
use App\Models\Admin;
use App\Services\Auth\Password\PasswordLinksService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * What an account hears of its own life · its invitation, and the word that the
 * address it signs in with changed.
 */
final readonly class AccountMailService
{
    public function __construct(private PasswordLinksService $links) {}

    /** Sends a new invitation, the link replacing the last one, and notes whether it left. */
    public function sendTheInvitation(Admin $account): bool
    {
        try {
            $token = $this->links->links(PasswordLinksService::INVITATIONS)->createToken($account);

            Mail::to($account->email)->send(new InvitationMail(
                $account->name,
                $account->email,
                $this->links->urlFor('auth.invitation', $account, $token),
                intdiv($this->links->minutesOf(PasswordLinksService::INVITATIONS), 1440),
            ));
        } catch (Throwable $failure) {
            Log::error('Sending an invitation to the back-office failed.', ['admin_id' => $account->id, 'exception' => $failure]);

            $account->forceFill(['invitation_failed_at' => CarbonImmutable::now()])->save();

            return false;
        }

        $account->forceFill(['invited_at' => CarbonImmutable::now(), 'invitation_failed_at' => null])->save();

        return true;
    }

    /** Tells both addresses that the one to sign in with changed · the password stays · and says whether it left. */
    public function tellOfTheNewAddress(Admin $account, string $formerAddress): bool
    {
        try {
            Mail::to([$account->email, $formerAddress])->send(new AddressChangedMail($account->name, $account->email));
        } catch (Throwable $failure) {
            Log::error('Telling of a new address failed.', ['admin_id' => $account->id, 'exception' => $failure]);

            return false;
        }

        return true;
    }
}
