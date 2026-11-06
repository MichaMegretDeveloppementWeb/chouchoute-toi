<?php

declare(strict_types=1);

namespace App\Services\Auth\Password;

use App\Models\Admin;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use LogicException;

/**
 * The links that let an account choose a password · the invitation of a new
 * account, and the forgotten password.
 *
 * A link is built on the site's own address, never on the request's: a host
 * name a visitor chose would otherwise carry the secret elsewhere.
 */
final readonly class PasswordLinksService
{
    /** The links that invite a new account, in `config/auth.php`. */
    public const string INVITATIONS = 'admin_invitations';

    /** The links that replace a forgotten password, in `config/auth.php`. */
    public const string PASSWORD_RESETS = 'admins';

    public function __construct(private PasswordBrokerManager $brokers) {}

    /** The links of one kind, as Laravel keeps them. */
    public function links(string $kind): PasswordBroker
    {
        $broker = $this->brokers->broker($kind);

        if (! $broker instanceof PasswordBroker) {
            throw new LogicException(sprintf('The %s links are kept by an unexpected broker.', $kind));
        }

        return $broker;
    }

    /** The kind of link a page was opened for · an invitation, or a forgotten password. */
    public function kindFor(string $purpose): string
    {
        return $purpose === 'invitation' ? self::INVITATIONS : self::PASSWORD_RESETS;
    }

    /** The minutes a link of this kind lives, as `config/auth.php` sets them. */
    public function minutesOf(string $kind): int
    {
        return (int) config('auth.passwords.'.$kind.'.expire');
    }

    /** The address a link leads to, on the site's own address. */
    public function urlFor(string $route, Admin $account, string $token): string
    {
        return rtrim((string) config('app.url'), '/').route($route, ['account' => $account->id, 'token' => $token], false);
    }

    /** Every link sent to this account's address stops working. */
    public function forgetTheLinksOf(Admin $account): void
    {
        $this->links(self::INVITATIONS)->deleteToken($account);
        $this->links(self::PASSWORD_RESETS)->deleteToken($account);
    }
}
