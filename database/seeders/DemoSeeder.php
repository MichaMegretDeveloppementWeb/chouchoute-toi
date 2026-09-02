<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Everything a local database needs to look like an establishment that works.
 *
 * One command instead of three:
 *
 *     php artisan db:seed --class="Database\Seeders\DemoSeeder"
 *
 * Deliberately outside {@see DatabaseSeeder}, which carries the real price list
 * and the real team and may therefore run on production. Nothing invented ever
 * goes through that door.
 */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // L'établissement d'abord · l'agenda pose ses rendez-vous dans des
        // lieux, et il en tient un de chaque genre au moment où il commence.
        $this->call([
            DemoEstablishmentSeeder::class,
            DemoCatalogueSeeder::class,
            DemoAgendaSeeder::class,
        ]);
    }
}
