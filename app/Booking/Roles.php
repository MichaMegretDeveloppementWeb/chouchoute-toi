<?php

declare(strict_types=1);

namespace App\Booking;

use App\Enums\Account\Role;
use Falcon\Booking\Contracts\Authorization\DeclaresRoles;
use Falcon\Booking\Data\Authorization\RoleData;
use Falcon\Booking\Enums\Authorization\Ability;
use Falcon\Booking\Enums\Authorization\Reach;

/**
 * What each role of the site opens in booking · the owner everything, a
 * collaborator the planning and the clients, changing only what is theirs and
 * erasing no client.
 */
final class Roles implements DeclaresRoles
{
    public function roles(): array
    {
        return [
            new RoleData(Role::Collaborator->value, Role::Collaborator->label(), 'Le planning et les clients. Il ne change que ses propres rendez-vous, et n’efface aucun client.', [
                Ability::Agenda->value => Reach::All,
                Ability::AppointmentsEdit->value => Reach::Theirs,
                Ability::UnavailabilitiesEdit->value => Reach::Theirs,
                Ability::ClientsErase->value => Reach::None,
            ]),
            new RoleData(Role::Owner->value, Role::Owner->label(), 'Tout le back-office, réglages, audience et marketing compris.', [
                Ability::Booking->value => Reach::All,
            ]),
        ];
    }
}
