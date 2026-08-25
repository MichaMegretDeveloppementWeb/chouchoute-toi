<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use Tests\TestCase;

/**
 * Le seeder de démonstration écrit à l'heure de l'établissement.
 *
 * Il a longtemps composé ses heures avec `CarbonImmutable::now()`, qui répond
 * dans le fuseau de l'application — UTC. Un `setTime(9, 0)` y signifiait neuf
 * heures UTC, soit onze à Paris : la démonstration plaçait ses visites deux
 * heures après l'ouverture, et son congé de 02:00 à 02:00.
 *
 * Rien ne l'avait attrapé parce que rien ne le regardait. Le seeder est trop
 * long à rejouer dans une suite, donc ce test le **lit** : c'est le même filet
 * que celui posé sur le JavaScript du calendrier, faute de pouvoir l'exécuter.
 */
final class DemoAgendaHoursTest extends TestCase
{
    /**
     * Le seeder, **commentaires retirés**.
     *
     * Sans quoi le test se prend à sa propre prose : ce fichier explique ce
     * qu'il ne fait plus, et citer `now()` pour dire qu'on l'a retiré suffisait
     * à faire échouer l'assertion. Ce qu'on veut lire est du code.
     */
    private function seeder(): string
    {
        $source = (string) file_get_contents(base_path('database/seeders/DemoAgendaSeeder.php'));

        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    public function test_it_takes_its_today_from_the_business_clock(): void
    {
        $source = $this->seeder();

        $this->assertStringContainsString('app(BusinessClock::class)->today()', $source);
        $this->assertStringContainsString('private CarbonImmutable $today;', $source);
    }

    /**
     * `now()` ne sert plus qu'à `blocked_at`, qui est un instant vrai et non une
     * heure de paroi : le moment où une fiche a été bloquée ne se lit pas au
     * cadran de l'institut.
     */
    public function test_now_is_left_only_where_an_instant_is_meant(): void
    {
        preg_match_all('/CarbonImmutable::now\(\)/', $this->seeder(), $appels);

        $this->assertCount(
            1,
            $appels[0],
            'Un `now()` de plus compose une heure de paroi dans le fuseau du serveur, pas dans celui de l’établissement.',
        );

        $this->assertMatchesRegularExpression(
            "/'blocked_at' => .*CarbonImmutable::now\(\)/",
            $this->seeder(),
            'Le seul `now()` qui reste doit être celui de `blocked_at`.',
        );
    }

    /**
     * Les visites se posent sur une table d'heures ouvertes, et non sur un
     * `9 + \$slot` qui traversait la pause du midi.
     *
     * Le décalage de deux heures masquait ce second défaut : les créneaux
     * tombaient alors de 11 h à 16 h, et l'erreur ne se voyait pas tant que
     * l'heure elle-même était fausse.
     */
    public function test_the_slots_avoid_the_midday_break(): void
    {
        $source = $this->seeder();

        $this->assertStringContainsString('private const SLOT_HOURS = [9, 10, 11, 14, 15, 16];', $source);
        $this->assertStringNotContainsString('9 + $slot', $source);

        // La pause déclarée plus haut dans le fichier, et aucune heure de la
        // table ne doit tomber dedans.
        preg_match('/MORNING = \[\'\d{2}:\d{2}\', \'(\d{2}):(\d{2})\'\]/', $source, $finMatin);
        preg_match('/AFTERNOON = \[\'(\d{2}):(\d{2})\'/', $source, $debutApresMidi);

        $this->assertNotEmpty($finMatin, 'La plage du matin doit rester lisible depuis ce test.');
        $this->assertNotEmpty($debutApresMidi);

        $ferme = (int) $finMatin[1];
        $rouvre = (int) $debutApresMidi[1];

        foreach ([9, 10, 11, 14, 15, 16] as $heure) {
            $this->assertFalse(
                $heure >= $ferme && $heure < $rouvre,
                "Une visite posée à {$heure} h tombe dans la pause du midi.",
            );
        }
    }
}
