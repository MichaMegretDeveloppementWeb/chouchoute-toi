<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Mail\Team\AddressChangedMail;
use App\Mail\Team\InvitationMail;
use App\Mail\Team\PasswordLinkMail;
use App\Models\Admin;
use Carbon\CarbonImmutable;
use Falcon\Booking\Enums\Team\AccessNotice;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Throwable;

/**
 * What the back-office sends its team: an invitation, a link to choose a new
 * password, and the word that an address changed.
 *
 * A link is built on the site's own address, never on the request's: a host
 * name a client chose would otherwise carry the secret elsewhere.
 */
final readonly class TeamMessagesService
{
    /** The links that invite a new member, in `config/auth.php`. */
    public const INVITATIONS = 'admin_invitations';

    /** The links that replace a forgotten password, in `config/auth.php`. */
    public const PASSWORD_RESETS = 'admins';

    public function __construct(private PasswordBrokerManager $brokers) {}

    /** Sends a new invitation, the link replacing the last one, and notes whether it left. */
    public function sendTheInvitation(Admin $account): AccessNotice
    {
        try {
            $token = $this->links(self::INVITATIONS)->createToken($account);

            Mail::to($account->email)->send(new InvitationMail(
                $account->name,
                $account->email,
                $this->linkTo('admin.invitation', $account, $token),
                intdiv((int) config('auth.passwords.'.self::INVITATIONS.'.expire'), 1440),
            ));
        } catch (Throwable $failure) {
            Log::error('Sending an invitation to the back-office failed.', ['admin_id' => $account->id, 'exception' => $failure]);

            $account->forceFill(['invitation_failed_at' => CarbonImmutable::now()])->save();

            return AccessNotice::InvitationFailed;
        }

        $account->forceFill(['invited_at' => CarbonImmutable::now(), 'invitation_failed_at' => null])->save();

        return AccessNotice::InvitationSent;
    }

    /** Sends a link to choose a new password, when this address is an account's · nothing says whether it is. */
    public function sendThePasswordLink(string $email): void
    {
        try {
            $this->links(self::PASSWORD_RESETS)->sendResetLink(['email' => $email], function (CanResetPassword $account, string $token): void {
                if (! $account instanceof Admin) {
                    return;
                }

                Mail::to($account->email)->send(new PasswordLinkMail(
                    $account->name,
                    $this->linkTo('admin.password.reset', $account, $token),
                    (int) config('auth.passwords.'.self::PASSWORD_RESETS.'.expire'),
                ));
            });
        } catch (Throwable $failure) {
            Log::error('Sending a password link failed.', ['exception' => $failure]);
        }
    }

    /** Tells both addresses that the one to sign in with changed · the password stays. */
    public function tellOfTheNewAddress(Admin $account, string $formerAddress): void
    {
        try {
            Mail::to([$account->email, $formerAddress])->send(new AddressChangedMail($account->name, $account->email));
        } catch (Throwable $failure) {
            Log::error('Telling of a new address failed.', ['admin_id' => $account->id, 'exception' => $failure]);
        }
    }

    /** Every link sent to this account's address stops working. */
    public function forgetTheLinksOf(Admin $account): void
    {
        $this->links(self::INVITATIONS)->deleteToken($account);
        $this->links(self::PASSWORD_RESETS)->deleteToken($account);
    }

    /** The links of one kind, as Laravel keeps them. */
    public function links(string $kind): PasswordBroker
    {
        $broker = $this->brokers->broker($kind);

        if (! $broker instanceof PasswordBroker) {
            throw new LogicException(sprintf('The %s links are kept by an unexpected broker.', $kind));
        }

        return $broker;
    }

    private function linkTo(string $route, Admin $account, string $token): string
    {
        return rtrim((string) config('app.url'), '/').route($route, ['account' => $account->id, 'token' => $token], false);
    }
}
