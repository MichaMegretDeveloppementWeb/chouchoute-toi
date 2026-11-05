<?php

declare(strict_types=1);

namespace App\Services\Team;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The accounts of the back-office, a suspended one being nobody.
 *
 * Every door goes through this: signing in, a session already open, a remembered
 * one, and asking for a new password. `auth:admin` guards the site, both packages
 * and their Livewire updates, so one suspension shuts them all at the next click.
 */
final class ActiveAdminUserProvider extends EloquentUserProvider
{
    /**
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    protected function newModelQuery($model = null)
    {
        $query = parent::newModelQuery($model);

        $query->getQuery()->whereNull('suspended_at');

        return $query;
    }
}
