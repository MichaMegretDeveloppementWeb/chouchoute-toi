<?php

declare(strict_types=1);

namespace App\Enums\Account;

use App\Analytics\Roles as AnalyticsRoles;
use App\Booking\Roles as BookingRoles;

/**
 * What a back-office account is · what each role opens is declared to the
 * packages by {@see BookingRoles} and {@see AnalyticsRoles}, the site's own
 * dashboard staying the owner's.
 */
enum Role: string
{
    case Owner = 'owner';
    case Collaborator = 'collaborator';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::Collaborator => 'Collaborateur',
        };
    }
}
