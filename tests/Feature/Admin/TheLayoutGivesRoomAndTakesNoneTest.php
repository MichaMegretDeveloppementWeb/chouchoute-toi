<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Notre gabarit rend la place, il ne décide pas ce qu'on en fait.
 *
 * Le back-office accueille nos écrans **et** ceux de deux paquets de la suite.
 * La règle qui vaut pour un hôte est alors celle-ci · le slot se rend en
 * entier, sans largeur maximale ni marge intérieure autour de lui. Chaque écran
 * porte sa propre boîte et s'y cadre — ceux qui se plafonnent comme ceux qui
 * prennent tout.
 *
 * **Un plafond posé ici s'ajouterait au leur et les enfermerait**, et rien ne le
 * dirait · une page qui perd la moitié de sa largeur ne déborde pas, elle
 * rétrécit. Il n'y a ni erreur, ni débordement, ni rien à lire dans un journal.
 *
 * **Le défaut a déjà eu lieu**, du côté du kit · une propriété `wide` y tenait
 * ce rôle et boîtait par défaut tout ce qui ne demandait rien. Le kit tient
 * désormais son côté. Celui-ci est le nôtre, et il est le seul qui reste · notre
 * gabarit s'intercale entre la coquille du kit et les écrans, donc un plafond
 * écrit ici serait invisible à la garde du kit.
 *
 * Lu sur la page rendue et non sur le fichier · ce qui compte est ce qu'un écran
 * reçoit, d'où qu'il vienne.
 */
final class TheLayoutGivesRoomAndTakesNoneTest extends TestCase
{
    /** Ce qu'un écran écrirait, et que rien ne doit encadrer. */
    private const MARKER = '<p id="un-ecran"></p>';

    public function test_the_admin_layout_caps_no_width_around_its_slot(): void
    {
        $room = $this->contentAreaBefore(
            Blade::render('<x-layout.admin>'.self::MARKER.'</x-layout.admin>'),
        );

        foreach (['max-w-', 'mx-auto', 'px-', 'py-'] as $cap) {
            $this->assertStringNotContainsString(
                $cap,
                $room,
                "Le gabarit encadre son slot avec « {$cap} ». Cette contrainte s’ajouterait à\n"
                .'celle de chaque écran · les nôtres comme ceux des paquets, qui portent déjà la leur.',
            );
        }
    }

    /**
     * Ce qui sépare la zone de contenu de l'écran qu'elle accueille.
     *
     * Découpé à la marque plutôt que lu dans la page entière · une largeur
     * maximale vit ailleurs légitimement, dans la barre latérale comme dans ce
     * qu'un écran se pose à lui-même, et une recherche globale répondrait sur
     * elles.
     */
    private function contentAreaBefore(string $page): string
    {
        $opens = strpos($page, '<main');

        $this->assertNotFalse($opens, 'La coquille ne rend plus de zone de contenu.');

        $screen = strpos($page, self::MARKER, $opens);

        $this->assertNotFalse($screen, 'Le contenu de l’écran n’est pas rendu dans la zone de contenu.');

        return substr($page, $opens, $screen - $opens);
    }
}
