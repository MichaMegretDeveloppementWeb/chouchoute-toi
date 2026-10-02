<?php

declare(strict_types=1);

namespace App\Services;

/**
 * How to reach the business, and where it travels, read from
 * `config/entreprise.php` · the pages call it rather than writing it out.
 */
final class BusinessContactService
{
    /** « 261 rue des Tattes, 74500 Publier ». */
    public static function address(): string
    {
        $address = (array) config('entreprise.adresse');

        return sprintf('%s, %s %s', $address['rue'] ?? '', $address['code_postal'] ?? '', $address['ville'] ?? '');
    }

    /** @return list<string> */
    public static function townsServed(): array
    {
        return array_values(array_map(strval(...), (array) config('entreprise.villes_desservies')));
    }

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
