<?php

declare(strict_types=1);

namespace Database\Seeders;

use Falcon\Booking\Models\Practitioner;
use Illuminate\Database\Seeder;

/**
 * L'agenda auquel tout se rattache.
 *
 * Sans praticienne, l'écran du planning n'a rien à afficher et le formulaire
 * refuse d'enregistrer : c'est la première ligne dont dépendent les horaires,
 * les indisponibilités et les rendez-vous.
 *
 * Idempotent, repéré au nom : rejouable en production sans écraser une fiche
 * déjà retouchée.
 */
final class PractitionerSeeder extends Seeder
{
    public function run(): void
    {
        Practitioner::query()->firstOrCreate(
            ['name' => 'Amandine'],
            [
                'is_bookable_online' => true,
                'position' => 0,
            ],
        );
    }
}
