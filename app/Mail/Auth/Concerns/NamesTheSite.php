<?php

declare(strict_types=1);

namespace App\Mail\Auth\Concerns;

/**
 * The name the sign-in messages say the site by · the sender's, as
 * `MAIL_FROM_NAME` sets it, so that one setting signs and names them all.
 */
trait NamesTheSite
{
    private function site(): string
    {
        return (string) config('mail.from.name');
    }
}
