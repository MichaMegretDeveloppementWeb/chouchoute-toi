<?php

declare(strict_types=1);

namespace Database\Seeders;

use Falcon\Booking\Enums\Catalogue\PricingMode;
use Falcon\Booking\Enums\Catalogue\Visibility;
use Falcon\Booking\Models\Service;
use Falcon\Booking\Models\ServiceCategory;
use Falcon\Booking\Support\Palette;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A catalogue large enough to judge the pickers by.
 *
 * The real price list is five ranges and fourteen treatments, which tells us
 * nothing about a two-column picker: everything fits, nothing scrolls, no label
 * is ever too long. This puts ten ranges and some seventy treatments in front of
 * the screens so the crowding shows.
 *
 * Deliberately absent from {@see DatabaseSeeder}: it is run by hand, like
 * {@see AdminSeeder}, and it has no business travelling with an ordinary seed.
 *
 * Idempotent on the slug, like {@see ServiceSeeder}, and additive: it never
 * touches what is already on file.
 */
final class DemoCatalogueSeeder extends Seeder
{
    /**
     * Ten ranges, five to ten treatments each.
     *
     * The names are those of a real salon rather than filler: a picker is judged
     * on the lengths it actually has to carry. Some are long on purpose, which
     * is what shows whether a column truncates where the meaning starts.
     *
     * Colours are named by family and rank rather than written out: ten ranges
     * spread over the wheel is the point, and a hexadecimal copied here falls
     * out of the grid the day the palette moves.
     *
     * @var array<string, array{color: string, services: list<array{0: string, 1: int, 2: int, 3?: PricingMode, 4?: int|null, 5?: Visibility, 6?: string}>}>
     */
    private const RANGES = [
        'Extensions de cils' => [
            'color' => Palette::FAMILIES['blue'][3],
            'services' => [
                ['Pose complète cil à cil', 120, 6500],
                ['Pose complète volume russe', 150, 8500],
                ['Remplissage 2 semaines', 60, 3500],
                ['Remplissage 3 semaines', 75, 4500],
                ['Remplissage 4 semaines', 90, 5500],
                ['Dépose complète avec soin réparateur', 30, 2000, PricingMode::Fixed, null, Visibility::Bookable, 'Dépose + soin'],
            ],
        ],
        'Rehaussement de cils' => [
            'color' => Palette::FAMILIES['blue'][1],
            'services' => [
                ['Rehaussement simple', 60, 5000],
                ['Rehaussement avec teinture', 75, 6000],
                ['Rehaussement et soin kératine longue durée', 90, 7000],
                ['Teinture des cils seule', 20, 1500],
                ['Retouche de rehaussement', 45, 3500],
            ],
        ],
        'Sourcils' => [
            'color' => Palette::FAMILIES['lilac'][3],
            'services' => [
                ['Épilation à la cire', 20, 1200],
                ['Épilation au fil', 25, 1500],
                ['Restructuration complète du sourcil', 45, 3000],
                ['Teinture', 20, 1500],
                ['Brow lift', 60, 4500],
                ['Henné et restructuration', 50, 3500],
                ['Rehaussement de sourcils longue tenue', 70, 5000],
            ],
        ],
        'Épilation visage' => [
            'color' => Palette::FAMILIES['sage'][1],
            'services' => [
                ['Lèvre supérieure', 10, 800],
                ['Menton', 10, 800],
                ['Joues', 15, 1200],
                ['Visage complet', 35, 2500],
                ['Nez et oreilles', 10, 900],
            ],
        ],
        'Épilation corps' => [
            'color' => Palette::FAMILIES['sage'][3],
            'services' => [
                ['Demi-jambes', 25, 1800],
                ['Jambes complètes', 45, 3000],
                ['Aisselles', 15, 1200],
                ['Maillot simple', 20, 1500],
                ['Maillot échancré', 25, 2000],
                ['Maillot intégral', 40, 3200],
                ['Dos ou torse', 35, 2800],
                ['Bras et avant-bras', 25, 1800],
            ],
        ],
        'Soins du visage' => [
            'color' => Palette::FAMILIES['amber'][2],
            'services' => [
                ['Nettoyage de peau en profondeur', 60, 5500],
                ['Soin hydratant éclat', 45, 4500],
                ['Soin anti-âge raffermissant', 75, 7500],
                ['Soin purifiant peaux mixtes à grasses', 60, 5500],
                ['Peeling doux aux acides de fruits', 50, 6000],
                ['Masque collagène et modelage du visage', 70, 7000],
            ],
        ],
        'Manucure' => [
            'color' => Palette::FAMILIES['rose'][3],
            'services' => [
                ['Manucure simple', 30, 2500],
                ['Pose de vernis semi-permanent', 60, 3500],
                ['Dépose et pose semi-permanent', 90, 4500],
                ['French manucure', 75, 4000],
                ['Nail art par ongle', 10, 500],
                ['Soin des mains et modelage', 30, 2800],
            ],
        ],
        'Beauté des pieds' => [
            'color' => Palette::FAMILIES['rose'][0],
            'services' => [
                ['Pédicure complète', 60, 4000],
                ['Vernis semi-permanent pieds', 45, 3000],
                ['Soin callosités et hydratation intense', 50, 3800],
                ['Beauté des pieds express', 25, 2000],
                ['Dépose vernis semi-permanent', 20, 1200],
            ],
        ],
        'Maquillage' => [
            'color' => Palette::FAMILIES['coral'][3],
            'services' => [
                ['Maquillage jour', 30, 3000],
                ['Maquillage soirée', 45, 4500],
                ['Maquillage mariée avec essai préalable', 120, 12000],
                ['Cours d\'auto-maquillage personnalisé', 90, 8000],
                ['Pose de faux cils bande', 15, 1000],
            ],
        ],
        'Forfaits et cures' => [
            'color' => Palette::FAMILIES['ochre'][2],
            'services' => [
                ['Forfait cils et sourcils', 150, 9500],
                ['Forfait mains et pieds', 120, 6500],

                // Les deux seules du catalogue à ne pas coûter un nombre : sans
                // elles, rien à l'écran ne montre les tarifs « à partir de » et
                // « sur devis », et c'est sur cette base qu'on les regarde.
                ['Cure de trois soins du visage', 180, 15000, PricingMode::From, 20000],

                // La seule qui s'affiche en ligne sans pouvoir être réservée :
                // un devis se discute, donc le client appelle. Sans elle, le
                // troisième état de visibilité ne se verrait nulle part sur la
                // base de développement.
                //
                // C'est aussi le nom le plus long du catalogue, donc celui qui
                // a le plus besoin d'une abréviation sur une carte.
                ['Forfait mariée complet, cheveux non inclus', 240, 0, PricingMode::Quote, null, Visibility::Shown, 'Forfait mariée'],

                ['Carte cadeau découverte', 60, 5000],
                ['Bilan beauté et conseil personnalisé', 45, 0],
            ],
        ],
    ];

    public function run(): void
    {
        $gammes = 0;
        $prestations = 0;
        $rangGamme = 100;

        foreach (self::RANGES as $nom => $gamme) {
            $slug = 'demo-'.Str::slug($nom);

            $categorie = ServiceCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $nom, 'color' => $gamme['color'], 'position' => $rangGamme++],
            );

            $gammes += $categorie->wasRecentlyCreated ? 1 : 0;

            $rangPrestation = 0;

            foreach ($gamme['services'] as $ligne) {
                [$nomPrestation, $minutes, $centimes] = $ligne;

                // Les deux derniers sont facultatifs : une prestation coûte un
                // nombre sauf mention contraire, et les soixante-dix autres
                // lignes n'ont pas à porter ce qu'elles n'utilisent pas.
                $mode = $ligne[3] ?? PricingMode::Fixed;
                $plafond = $ligne[4] ?? null;
                $visibilite = $ligne[5] ?? Visibility::Bookable;
                $abreviation = $ligne[6] ?? null;

                $prestation = Service::query()->firstOrCreate(
                    ['slug' => $slug.'-'.Str::slug($nomPrestation)],
                    [
                        'service_category_id' => $categorie->id,
                        'name' => $nomPrestation,
                        'abbreviation' => $abreviation,
                        'duration_minutes' => $minutes,
                        'pricing' => $mode,
                        'price_cents' => $centimes,
                        'price_max_cents' => $plafond,
                        'color' => $gamme['color'],
                        'visibility' => $visibilite,
                        'position' => $rangPrestation++,
                    ],
                );

                $prestations += $prestation->wasRecentlyCreated ? 1 : 0;
            }
        }

        $this->command?->info(
            "{$gammes} gamme(s) et {$prestations} prestation(s) créées, le reste était déjà en base."
        );
    }
}
