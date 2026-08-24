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
     * **Les vraies données, et rien d'autre** : l'équipe et le tarif. Ce seeder
     * peut donc tourner en production sans rien y inventer.
     *
     * `AdminSeeder` n'est pas ici : il crée un compte, donc il se lance à la
     * main, quand on sait pour qui. Le rejouer sans y penser sur une base de
     * production est une porte ouverte, pas un jeu d'essai.
     *
     * `DemoSeeder` non plus, et pour la raison inverse : ce qu'il pose est
     * inventé. Il se lance à la main sur une base locale.
     */
    public function run(): void
    {
        $this->call([
            PractitionerSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
