<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Account\Role;
use Carbon\CarbonImmutable;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Back-office account.
 *
 * Deliberately kept apart from User (guard `web`, reserved for future client
 * accounts): falcon/analytics excludes internal traffic by guard name, so the
 * administration needs a guard of its own.
 *
 * Its role, its practitioner and its access never come from a form as such ·
 * they are not fillable, and whoever writes them does it by name.
 *
 * @property Role $role
 * @property int|null $practitioner_id
 * @property CarbonImmutable|null $email_verified_at
 * @property CarbonImmutable|null $invited_at
 * @property CarbonImmutable|null $invitation_failed_at
 * @property CarbonImmutable|null $suspended_at
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, Notifiable;

    /**
     * The same defaults as the columns carry · an account just created is read
     * by the rules before anyone reloads it. The role has none · whoever writes
     * an account names it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'practitioner_id' => null,
        'invited_at' => null,
        'invitation_failed_at' => null,
        'suspended_at' => null,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'invited_at' => 'immutable_datetime',
            'invitation_failed_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }
}
