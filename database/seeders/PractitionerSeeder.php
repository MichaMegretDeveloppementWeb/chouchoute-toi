<?php

declare(strict_types=1);

namespace Database\Seeders;

use Falcon\Booking\Models\Practitioner;
use Falcon\Booking\Support\Palette;
use Illuminate\Database\Seeder;

/**
 * The agenda everything else hangs from. Without a practitioner the planning
 * screen has nothing to show and the form refuses to save: this is the first
 * row that opening hours, unavailabilities and appointments depend on.
 *
 * The other two come after her, and for judging: the form's selector, the
 * « occupé » mention and splitting a visit across several hands cannot be
 * judged on one person. They carry no hours, which serve online booking alone.
 *
 * Idempotent, matched on the name: replayable in production without
 * overwriting a record already edited.
 */
final class PractitionerSeeder extends Seeder
{
    /** @var list<array{name: string, position: int, color: string}> */
    private const EQUIPE = [
        // Three distinct families from the same palette as the treatments, so a
        // practitioner and a treatment are never confused. Named by family and
        // rank and never in hexadecimal: the palette moves, these follow.
        ['name' => 'Amandine', 'position' => 0, 'color' => Palette::DEFAULT_HUE],
        ['name' => 'Camille', 'position' => 1, 'color' => Palette::FAMILIES['sage'][3]],
        ['name' => 'Sarah', 'position' => 2, 'color' => Palette::FAMILIES['amber'][3]],
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
