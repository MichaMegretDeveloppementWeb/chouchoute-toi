<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The real data and nothing else: the team and the price list. This seeder
     * can therefore run in production without inventing anything there.
     *
     * `AdminSeeder` is not here: it creates an account, so it runs by hand,
     * when one knows for whom. `DemoSeeder` is not either, for the opposite
     * reason: what it lays is invented.
     */
    public function run(): void
    {
        $this->call([
            PractitionerSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
