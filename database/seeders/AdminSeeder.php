<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Account\Role;
use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * Creates the back-office account, the owner of the office.
 *
 * Idempotent on purpose: it is meant to be replayable on production, where a
 * second run must never reset a password that has been changed since.
 */
final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::query()->firstOrNew(['email' => 'amandine@chouchoute-toi.com']);

        if ($admin->exists) {
            $this->command?->info("Compte administrateur déjà présent : {$admin->email}, rien à faire.");

            return;
        }

        $admin->forceFill([
            'name' => 'Amandine',
            'password' => 'password',
            'email_verified_at' => now(),
            'role' => Role::Owner,
        ])->save();

        $this->command?->info("Compte administrateur créé : {$admin->email} (mot de passe : password).");
        $this->command?->warn('Changez ce mot de passe dès la première connexion.');
    }
}
