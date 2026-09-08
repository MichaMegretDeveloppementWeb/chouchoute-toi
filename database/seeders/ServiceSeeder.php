<?php

declare(strict_types=1);

namespace Database\Seeders;

use Falcon\Booking\Enums\Catalogue\Visibility;
use Falcon\Booking\Models\Practitioner;
use Falcon\Booking\Models\Service;
use Falcon\Booking\Models\ServiceCategory;
use Falcon\Booking\Support\Palette;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Moves the price list out of config/tarifs.php and into the database, where
 * names, durations, prices and colours can be edited from the back office.
 *
 * Each range of the price list becomes a category, and its treatments hang from
 * it. Flattened, the fifteen of them landed under « sans catégorie », which is
 * how the catalogue screen shows what belongs nowhere.
 *
 * Idempotent, keyed on the slug: replayable on production without touching a
 * service that has already been adjusted.
 */
final class ServiceSeeder extends Seeder
{
    /**
     * One colour per volume, so the agenda reads at a glance: the blue family
     * from its lightest shade to its deepest, and a lilac for what is not a
     * volume.
     *
     * Read twice, by the range and by the treatments filed under it: the
     * heading of a section and the rows beneath it then say the same family.
     * `depose` is the exception and belongs to a treatment alone, which is
     * filed under no category at all.
     *
     * A family and a rank, never a hexadecimal: what matters is the place in
     * the scale and not the colour itself. Written out in full it leaves the
     * grid the day the palette moves, and the form then shows a lone swatch
     * where it should tick a box. `SeededColoursComeFromThePaletteTest` holds
     * the rule.
     */
    private const COLORS = [
        'naturelle' => Palette::FAMILIES['blue'][0],
        'volume-leger' => Palette::FAMILIES['blue'][1],
        'volume-mixte' => Palette::FAMILIES['blue'][2],
        'volume-intense' => Palette::FAMILIES['blue'][3],
        'depose' => Palette::FAMILIES['lilac'][0],
    ];

    public function run(): void
    {
        $created = 0;
        $categories = 0;
        $rank = 0;

        foreach (config('tarifs.categories') as $slug => $category) {
            // The range carries its treatments' colour: a family reads as a
            // family, from the section title down to the rows.
            $range = ServiceCategory::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $category['nom'],
                    'description' => $category['description'],
                    'color' => self::COLORS[$slug] ?? Palette::DEFAULT_HUE,
                    'position' => $rank++,
                ],
            );

            $categories += $range->wasRecentlyCreated ? 1 : 0;

            // Restarted per range: a position orders treatments inside their
            // category, and the catalogue screen reads it that way.
            $position = 0;

            $created += $this->createService(
                name: $category['pose']['nom'],
                slug: $slug.'-pose-complete',
                description: $category['description'],
                duration: $this->durationToMinutes($category['pose']['duree']),
                priceCents: $category['pose']['prix'] * 100,
                color: self::COLORS[$slug] ?? Palette::DEFAULT_HUE,
                position: $position++,
                categoryId: $range->id,
            );

            foreach ($category['remplissages'] as $refill) {
                $created += $this->createService(
                    name: $category['nom'].' : '.$refill['nom'],
                    slug: $slug.'-'.Str::slug($refill['nom']),
                    description: $refill['description'],
                    duration: $this->durationToMinutes($refill['duree']),
                    priceCents: $refill['prix'] * 100,
                    color: self::COLORS[$slug] ?? Palette::DEFAULT_HUE,
                    position: $position++,
                    categoryId: $range->id,
                );
            }
        }

        $depose = config('tarifs.depose');

        // Deliberately under no category: it belongs to no range, and the
        // catalogue has a place for exactly that.
        $created += $this->createService(
            name: $depose['nom'],
            slug: 'depose',
            description: $depose['description'],
            duration: $this->durationToMinutes($depose['duree']),
            priceCents: $depose['prix'] * 100,
            color: self::COLORS['depose'],
            position: 0,
            categoryId: null,
        );

        $this->command?->info(
            "{$categories} gamme(s) et {$created} prestation(s) créée(s), le reste était déjà en base."
        );
    }

    private function createService(
        string $name,
        string $slug,
        string $description,
        int $duration,
        int $priceCents,
        string $color,
        int $position,
        ?int $categoryId,
    ): int {
        $service = Service::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'service_category_id' => $categoryId,
                'name' => $name,
                'description' => $description,
                'duration_minutes' => $duration,
                'price_cents' => $priceCents,
                'color' => $color,
                'visibility' => Visibility::Bookable,
                'position' => $position,
            ],
        );

        if ($service->wasRecentlyCreated) {
            // The whole team. On a treatment already there nothing is touched:
            // a skill removed by hand must not come back on the next replay.
            $service->practitioners()->sync(Practitioner::query()->pluck('id')->all());
        }

        return $service->wasRecentlyCreated ? 1 : 0;
    }

    /** Parses the human durations of the price list: "2h", "2h 15min", "1h". */
    private function durationToMinutes(string $duration): int
    {
        preg_match('/(?:(\d+)\s*h)?\s*(?:(\d+)\s*min)?/', $duration, $matches);

        return ((int) ($matches[1] ?? 0)) * 60 + (int) ($matches[2] ?? 0);
    }
}
