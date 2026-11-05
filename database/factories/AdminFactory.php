<?php

namespace Database\Factories;

use App\Enums\Team\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => AdminRole::Owner,
        ];
    }

    /** An account of the team, for this member. */
    public function practitioner(int $practitionerId): self
    {
        return $this->state(['role' => AdminRole::Practitioner, 'practitioner_id' => $practitionerId]);
    }
}
