<?php

declare(strict_types=1);

namespace App\Services\Auth\Account;

use App\Enums\Account\Role;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The owners in service, held while an account changes · « at least one owner »
 * is not a row, so every write that could leave none takes the rows of the
 * owners first, one by one by ascending number, and decides on what it reads
 * under that lock.
 *
 * It runs inside the transaction of a package that has already read · every
 * read here is a locking one, which sees what others committed meanwhile.
 */
final readonly class OwnerLockService
{
    /**
     * Holds the owners in service until the transaction ends, and says who they
     * are now · an owner demoted or suspended while this waited is not among
     * them, and one promoted meanwhile is.
     *
     * @return list<int>
     *
     * @throws LogicException outside a transaction, where a lock is let go at once
     */
    public function holdTheOwners(): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('The owners are held inside a transaction.');
        }

        $candidates = Admin::query()->where('role', Role::Owner)->where('suspended_at', null);
        $candidates->getQuery()->orderBy('id')->sharedLock();

        $held = [];

        foreach ($candidates->pluck('id') as $id) {
            $owner = Admin::query()->whereKey($id);
            $owner->getQuery()->lockForUpdate();
            $locked = $owner->first(['id', 'role', 'suspended_at']);

            if ($locked?->role === Role::Owner && $locked->suspended_at === null) {
                $held[] = $locked->id;
            }
        }

        return $held;
    }
}
