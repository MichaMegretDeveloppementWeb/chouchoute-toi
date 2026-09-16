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
     * The application's own, and nothing else. It owns four tables — users,
     * cache, jobs and admins — and none of them is seeded here today.
     *
     * **A package's seeders are not declared here.** They live inside the
     * package and are played by the command it exposes, with its own options
     * and its own refusal to run outside development:
     *
     *     php artisan booking:seed
     *
     * Declaring them here would tie this file to table names that move from one
     * version to the next, and `db:seed` would then lay hundreds of invented
     * appointments every time anyone wanted to reseed anything at all.
     *
     * `AdminSeeder` is not here either, and deliberately: it creates an account,
     * so it runs by hand, when one knows for whom.
     */
    public function run(): void
    {
        //
    }
}
