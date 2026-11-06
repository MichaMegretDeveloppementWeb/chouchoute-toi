<?php

declare(strict_types=1);

namespace App\Enums\Account;

use App\Providers\GateServiceProvider;

/**
 * What a back-office account may open · the rules themselves are in
 * {@see GateServiceProvider}.
 */
enum Role: string
{
    case Owner = 'owner';
    case Practitioner = 'practitioner';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::Practitioner => 'Praticien',
        };
    }

    /** What the role gives, as the fiche of a practitioner says it. */
    public function opens(): string
    {
        return match ($this) {
            self::Owner => 'Tout le back-office, réglages, audience et marketing compris.',
            self::Practitioner => 'Le planning et les clients. Il ne change que ses propres rendez-vous, et n’efface aucun client.',
        };
    }

    /** The name of the route an account of this role arrives on. */
    public function home(): string
    {
        return match ($this) {
            self::Owner => 'admin.dashboard',
            self::Practitioner => 'booking.admin.agenda',
        };
    }

    /** The button that leads back to the page of the role. */
    public function backHome(): string
    {
        return match ($this) {
            self::Owner => 'Revenir au tableau de bord',
            self::Practitioner => 'Revenir au Planning',
        };
    }
}
