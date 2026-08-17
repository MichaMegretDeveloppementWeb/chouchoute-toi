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
     * `AdminSeeder` n'est pas ici : il crée un compte, donc il se lance à la
     * main, quand on sait pour qui. Le rejouer sans y penser sur une base de
     * production est une porte ouverte, pas un jeu d'essai.
     */
    public function run(): void
    {
        $this->call([
            PractitionerSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
