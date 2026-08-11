<?php

declare(strict_types=1);

namespace Database\Seeders;

use Falcon\Booking\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Moves the price list out of config/tarifs.php and into the database, where
 * Amandine can edit names, durations, prices and colours herself.
 *
 * Idempotent, keyed on the slug: replayable on production without touching a
 * service she has already adjusted.
 */
final class ServiceSeeder extends Seeder
{
    /** One colour per volume, so the agenda reads at a glance. */
    private const COLORS = [
        'naturelle' => '#D4A89A',
        'volume-leger' => '#C48B7C',
        'volume-mixte' => '#8A5A4E',
        'volume-intense' => '#512731',
        'depose' => '#7A7A7A',
    ];

    public function run(): void
    {
        $position = 0;
        $created = 0;

        foreach (config('tarifs.categories') as $slug => $category) {
            $created += $this->createService(
                name: $category['pose']['nom'],
                slug: $slug.'-pose-complete',
                description: $category['description'],
                duration: $this->durationToMinutes($category['pose']['duree']),
                priceCents: $category['pose']['prix'] * 100,
                color: self::COLORS[$slug] ?? '#512731',
                position: $position++,
            );

            foreach ($category['remplissages'] as $refill) {
                $created += $this->createService(
                    name: $category['nom'].' : '.$refill['nom'],
                    slug: $slug.'-'.Str::slug($refill['nom']),
                    description: $refill['description'],
                    duration: $this->durationToMinutes($refill['duree']),
                    priceCents: $refill['prix'] * 100,
                    color: self::COLORS[$slug] ?? '#512731',
                    position: $position++,
                );
            }
        }

        $depose = config('tarifs.depose');

        $created += $this->createService(
            name: $depose['nom'],
            slug: 'depose',
            description: $depose['description'],
            duration: $this->durationToMinutes($depose['duree']),
            priceCents: $depose['prix'] * 100,
            color: self::COLORS['depose'],
            position: $position,
        );

        $this->command?->info("{$created} prestation(s) créée(s), le reste était déjà en base.");
    }

    private function createService(
        string $name,
        string $slug,
        string $description,
        int $duration,
        int $priceCents,
        string $color,
        int $position,
    ): int {
        $service = Service::query()->firstOrCreate(
            ['slug' => $slug],
            [
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
