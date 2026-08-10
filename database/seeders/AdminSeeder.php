<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * Creates the back-office account.
 *
 * Idempotent on purpose: it is meant to be replayable on production, where a
 * second run must never reset a password that has been changed since.
 */
final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::query()->firstOrCreate(
            ['email' => 'amandine@chouchoute-toi.com'],
            [
                'name' => 'Amandine',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        if ($admin->wasRecentlyCreated) {
            $this->command?->info("Compte administrateur créé : {$admin->email} (mot de passe : password).");
            $this->command?->warn('Changez ce mot de passe dès la première connexion.');

            return;
        }

        $this->command?->info("Compte administrateur déjà présent : {$admin->email}, rien à faire.");
    }
}
