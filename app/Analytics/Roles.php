<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Enums\Account\Role;
use Falcon\Analytics\Contracts\Authorization\DeclaresRoles;
use Falcon\Analytics\DTOs\Authorization\RoleData;
use Falcon\Analytics\Enums\Authorization\Ability;
use Falcon\Analytics\Enums\Authorization\Reach;

/**
 * What each role of the site opens in analytics · the audience and the
 * marketing are the owner's, and a collaborator is declared to open nothing.
 */
final class Roles implements DeclaresRoles
{
    public function roles(): array
    {
        return [
            new RoleData(Role::Owner->value, [
                Ability::Analytics->value => Reach::All,
            ]),
            new RoleData(Role::Collaborator->value),
        ];
    }
}
