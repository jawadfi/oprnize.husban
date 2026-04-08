<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Resolve the company_id that the authenticated user belongs to.
     */
    private function resolveCompanyId(Admin|User|Company $user): ?int
    {
        if ($user instanceof Admin) {
            return null; // Admin sees all — handled by returning true early
        }

        if ($user instanceof Company) {
            return $user->id;
        }

        if ($user instanceof User) {
            return $user->company_id;
        }

        return null;
    }

    /**
     * Check whether the employee belongs to the authenticated company/user.
     */
    private function ownsEmployee(Admin|User|Company $user, Employee $employee): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        $companyId = $this->resolveCompanyId($user);

        return $companyId !== null && (int) $employee->company_id === $companyId;
    }

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
    public function view(Admin|User|Company $user, Employee $employee): bool
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
    public function update(Admin|User|Company $user, Employee $employee): bool
    {
        return $this->ownsEmployee($user, $employee);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin|User|Company $user, Employee $employee): bool
    {
        return $this->ownsEmployee($user, $employee);
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
    public function forceDelete(Admin|User|Company $user, Employee $employee): bool
    {
        return $this->ownsEmployee($user, $employee);
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
    public function restore(Admin|User|Company $user, Employee $employee): bool
    {
        return $this->ownsEmployee($user, $employee);
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
    public function replicate(Admin|User|Company $user, Employee $employee): bool
    {
        return $this->ownsEmployee($user, $employee);
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(Admin|User|Company $user): bool
    {
        return true;
    }
}

