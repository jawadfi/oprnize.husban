<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Admin|User $user): bool
    {
        return true; // Temporarily allow all access
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Admin|User $user, Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin|User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin|User $user, Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin|User $user, Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(Admin|User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(Admin|User $user, Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(Admin|User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(Admin|User $user, Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(Admin|User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(Admin|User $user, Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(Admin|User $user): bool
    {
        return true;
    }
}
