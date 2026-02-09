<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Payroll;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Admin|User|Company $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin|User|Company $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin|User|Company $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(Admin|User|Company $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(Admin|User|Company $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(Admin|User|Company $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(Admin|User|Company $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(Admin|User|Company $user): bool
    {
        return true;
    }
}



