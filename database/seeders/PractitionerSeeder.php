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
 * Camille et Sarah viennent après elle, et pour l'essai : le sélecteur du
 * formulaire, la mention « occupé » et la répartition d'une visite entre
 * plusieurs mains ne se jugent pas à une seule personne. Elles n'ont pas
 * d'horaires, qui ne serviront qu'à la réservation en ligne, et l'écran des
 * horaires ne pilote pour l'instant que la première.
 *
 * La couleur ne sert encore à rien : le planning n'a qu'une colonne. Elle est
 * posée pour le jour où il en aura une par personne.
 *
 * Idempotent, repéré au nom : rejouable en production sans écraser une fiche
 * déjà retouchée.
 */
final class PractitionerSeeder extends Seeder
{
    /** @var list<array{name: string, position: int, color: string}> */
    private const EQUIPE = [
        ['name' => 'Amandine', 'position' => 0, 'color' => '#512731'],
        ['name' => 'Camille', 'position' => 1, 'color' => '#3f6d63'],
        ['name' => 'Sarah', 'position' => 2, 'color' => '#8a5a2b'],
    ];

    public function run(): void
    {
        foreach (self::EQUIPE as $personne) {
            Practitioner::query()->firstOrCreate(
                ['name' => $personne['name']],
                [
                    'is_bookable_online' => true,
                    'position' => $personne['position'],
                    'color' => $personne['color'],
                ],
            );
        }
    }
}
