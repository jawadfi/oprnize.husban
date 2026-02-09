<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Deduction;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeductionPolicy
{
    use HandlesAuthorization;

    public function viewAny(Admin|User|Company $user): bool
    {
        return true;
    }

    public function view(Admin|User|Company $user, Deduction $deduction): bool
    {
        return true;
    }

    public function create(Admin|User|Company $user): bool
    {
        return true;
    }

    public function update(Admin|User|Company $user, Deduction $deduction): bool
    {
        return true;
    }

    public function delete(Admin|User|Company $user, Deduction $deduction): bool
    {
        return true;
    }

    public function deleteAny(Admin|User|Company $user): bool
    {
        return true;
    }

    public function forceDelete(Admin|User|Company $user, Deduction $deduction): bool
    {
        return true;
    }

    public function forceDeleteAny(Admin|User|Company $user): bool
    {
        return true;
    }

    public function restore(Admin|User|Company $user, Deduction $deduction): bool
    {
        return true;
    }

    public function restoreAny(Admin|User|Company $user): bool
    {
        return true;
    }

    public function replicate(Admin|User|Company $user, Deduction $deduction): bool
    {
        return true;
    }

    public function reorder(Admin|User|Company $user): bool
    {
        return true;
    }
}
