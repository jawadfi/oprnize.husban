<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin;
use App\Models\Company;

use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function view(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function deleteAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function forceDelete(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function forceDeleteAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restoreAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function replicate(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can reorder.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function reorder(Admin|User|Company $user): bool
    {
        return true;
    }
}



