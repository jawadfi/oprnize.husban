<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin;
use App\Models\Payroll;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete($user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore($user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate($user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder($user): bool
    {
        return true;
    }
}



