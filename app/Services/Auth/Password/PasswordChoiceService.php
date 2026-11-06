<?php

declare(strict_types=1);

namespace App\Services\Auth\Password;

use App\Models\Admin;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Choosing a password from a link · the invitation of a new account, or a
 * forgotten password. A link that cannot serve says nothing of why.
 */
final readonly class PasswordChoiceService
{
    public function __construct(private PasswordLinksService $links) {}

    /** The account a link names, unless it was suspended since. */
    public function holderOf(int $account): ?Admin
    {
        $user = Auth::createUserProvider('admins')?->retrieveById($account);

        return $user instanceof Admin ? $user : null;
    }

    /** Whether this link still serves its account. */
    public function serves(?Admin $holder, string $token, string $purpose): bool
    {
        return $holder !== null && $this->links->links($this->links->kindFor($purpose))->tokenExists($holder, $token);
    }

    /**
     * Writes the password the link was opened for, the account's other devices
     * forgetting it · false when the link no longer serves.
     */
    public function choose(Admin $holder, string $token, string $purpose, string $password): bool
    {
        $status = $this->links->links($this->links->kindFor($purpose))->reset([
            'email' => $holder->email,
            'token' => $token,
            'password' => $password,
        ], static function (Admin $account, string $password): void {
            $account->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'email_verified_at' => $account->email_verified_at ?? CarbonImmutable::now(),
            ])->save();
        });

        return $status === PasswordBroker::PASSWORD_RESET;
    }
}
