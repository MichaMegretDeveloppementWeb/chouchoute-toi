<?php

declare(strict_types=1);

namespace App\Services;

/**
 * How to reach the business, read from `config/entreprise.php` · the pages
 * call it rather than writing the address or the number out.
 */
final class BusinessContactService
{
    public static function email(): string
    {
        return (string) config('entreprise.email');
    }

    /** The number as a `tel:` link expects it, in international form. */
    public static function phone(): string
    {
        return (string) config('entreprise.telephone');
    }

    /**
     * A French number as people read it · « +33639981234 » becomes
     * « 06 39 98 12 34 ». Any other number is returned as it is.
     */
    public static function phoneForDisplay(?string $phone = null): string
    {
        $phone ??= self::phone();

        if (preg_match('/\A\+33(\d{9})\z/', $phone, $digits) !== 1) {
            return $phone;
        }

        return implode(' ', str_split('0'.$digits[1], 2));
    }
}
