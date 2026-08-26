<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use Falcon\Booking\Support\Palette;
use Tests\TestCase;

/**
 * Les seeders ne posent que des nuances de la palette.
 *
 * Le formulaire d'une prestation propose une grille ; une couleur qui n'en fait
 * pas partie s'y affiche en pastille isolée, avec une phrase pour l'expliquer.
 * C'est le bon comportement pour l'exception. Si les seeders posent des
 * couleurs d'ailleurs, l'exception devient la règle et la grille ne correspond
 * plus à rien.
 *
 * Les seeders sont **lus** et non rejoués : le catalogue de démonstration est
 * long à écrire, et c'est le même filet que `DemoAgendaHoursTest` pose déjà.
 */
final class SeededColoursComeFromThePaletteTest extends TestCase
{
    /** @var list<string> */
    private const SEEDERS = [
        'DemoCatalogueSeeder.php',
        'ServiceSeeder.php',
        'PractitionerSeeder.php',
    ];

    /**
     * Le fichier, **commentaires retirés**.
     *
     * Sans quoi le test se prend à sa propre prose : un commentaire qui cite
     * l'ancienne couleur pour dire qu'on l'a quittée suffirait à le faire
     * échouer. Ce qu'on veut lire est du code.
     */
    private function code(string $seeder): string
    {
        $source = (string) file_get_contents(base_path('database/seeders/'.$seeder));

        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    public function test_every_colour_a_seeder_writes_is_in_the_grid(): void
    {
        $seen = 0;

        foreach (self::SEEDERS as $seeder) {
            preg_match_all("/'(#[0-9A-Fa-f]{6})'/", $this->code($seeder), $found);

            foreach ($found[1] as $colour) {
                $seen++;

                $this->assertTrue(
                    Palette::offers($colour),
                    $seeder.' pose '.$colour.', que la grille ne propose pas.',
                );
            }
        }

        $this->assertGreaterThan(
            10,
            $seen,
            'Aucune couleur lue : le motif ne trouve plus rien et ce test ne garde rien.',
        );
    }

    /**
     * Ce qui n'est pas écrit en clair passe par la constante, et non par une
     * valeur recopiée à côté d'elle.
     */
    public function test_the_fallbacks_go_through_the_constant(): void
    {
        foreach (['ServiceSeeder.php', 'PractitionerSeeder.php'] as $seeder) {
            $this->assertStringContainsString('Palette::DEFAULT_HUE', $this->code($seeder));
        }
    }
}
