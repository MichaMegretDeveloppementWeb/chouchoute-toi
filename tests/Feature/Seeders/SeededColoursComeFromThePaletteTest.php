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
 * Ce test lisait les hexadécimaux et vérifiait que la grille les proposait.
 * Il en trouvait dix-sept, et il les a tous validés jusqu'au jour où la palette
 * a bougé : six d'entre eux en sont sortis d'un coup, la famille moutarde
 * entière comprise. Un test qui passe la veille et échoue le lendemain sans que
 * les seeders aient été touchés dit qu'on garde la mauvaise chose.
 *
 * **Ce qu'on garde désormais est qu'aucun hexadécimal n'y soit écrit.** Une
 * famille et un rang ne peuvent pas sortir de la grille : ils la désignent.
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

    public function test_no_seeder_writes_a_colour_by_hand(): void
    {
        foreach (self::SEEDERS as $seeder) {
            preg_match_all("/'(#[0-9A-Fa-f]{6})'/", $this->code($seeder), $found);

            $this->assertSame(
                [],
                $found[1],
                $seeder.' écrit '.implode(', ', $found[1]).' en clair. Une couleur se nomme par sa '
                .'famille et son rang — Palette::FAMILIES[…][…] — sans quoi elle quitte la grille '
                .'à la première retouche de la palette.',
            );
        }
    }

    /**
     * Et une famille nommée existe.
     *
     * Le vrai risque de la notation par rang : `FAMILIES['gold'][0]` sur une
     * palette qui n'a plus de `gold` ne lève pas d'exception, il rend `null`.
     * La prestation serait semée sans couleur, en silence.
     */
    public function test_every_family_a_seeder_names_exists(): void
    {
        $read = 0;

        foreach (self::SEEDERS as $seeder) {
            preg_match_all(
                "/Palette::FAMILIES\['([a-z]+)'\]\[(\d+)\]/",
                $this->code($seeder),
                $found,
                PREG_SET_ORDER,
            );

            foreach ($found as [, $family, $rank]) {
                $read++;

                $this->assertArrayHasKey(
                    $family,
                    Palette::FAMILIES,
                    $seeder.' nomme la famille « '.$family.' », que la palette ne tient plus.',
                );

                $this->assertArrayHasKey(
                    (int) $rank,
                    Palette::FAMILIES[$family],
                    $seeder.' demande le rang '.$rank.' de « '.$family.' », qui n\'en a que '
                    .count(Palette::FAMILIES[$family]).'.',
                );
            }
        }

        $this->assertGreaterThan(
            10,
            $read,
            'Aucune famille lue : le motif ne trouve plus rien et ce test ne garde rien.',
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
