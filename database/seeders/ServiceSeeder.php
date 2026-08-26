<?php

declare(strict_types=1);

namespace Database\Seeders;

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
     * from its lightest shade to its deepest, and a violet for what is not a
     * volume.
     *
     * Every value is a shade of `Falcon\Booking\Support\Palette`, and
     * `TheSeededColoursComeFromThePaletteTest` holds it: a treatment seeded on
     * a colour the grid does not offer would show a swatch of its own in the
     * form, for no reason anyone could name.
     */
    private const COLORS = [
        'naturelle' => '#AAD3F4',
        'volume-leger' => '#95C8F1',
        'volume-mixte' => '#7EBDEE',
        'volume-intense' => '#66B1EB',
        'depose' => '#D2C9F5',
    ];

    public function run(): void
    {
        $created = 0;
        $categories = 0;
        $rank = 0;

        foreach (config('tarifs.categories') as $slug => $category) {
            $range = ServiceCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $category['nom'], 'description' => $category['description'], 'position' => $rank++],
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
                'is_bookable_online' => true,
                'position' => $position,
            ],
        );

        return $service->wasRecentlyCreated ? 1 : 0;
    }

    /** Parses the human durations of the price list: "2h", "2h 15min", "1h". */
    private function durationToMinutes(string $duration): int
    {
        preg_match('/(?:(\d+)\s*h)?\s*(?:(\d+)\s*min)?/', $duration, $matches);

        return ((int) ($matches[1] ?? 0)) * 60 + (int) ($matches[2] ?? 0);
    }
}
