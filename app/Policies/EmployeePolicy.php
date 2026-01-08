<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return $user->can('view_any_employee');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Employee $employee): bool
    {
        return $user->can('view_employee');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->can('create_employee');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Employee $employee): bool
    {
        return $user->can('update_employee');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Employee $employee): bool
    {
        return $user->can('delete_employee');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny($user): bool
    {
        return $user->can('delete_any_employee');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete($user, Employee $employee): bool
    {
        return $user->can('force_delete_employee');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny($user): bool
    {
        return $user->can('force_delete_any_employee');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore($user, Employee $employee): bool
    {
        return $user->can('restore_employee');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny($user): bool
    {
        return $user->can('restore_any_employee');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate($user, Employee $employee): bool
    {
        return $user->can('replicate_employee');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder($user): bool
    {
        return $user->can('reorder_employee');
    }
}
