<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewVerificationDocuments(User $actor, User $target): bool
    {
        return $actor->role === Role::Secretary
            && $target->role === Role::Resident
            && $target->barangay_id === $actor->barangay_id;
    }

    public function manageResident(User $actor, User $target): bool
    {
        return $target->role === Role::Resident && $target->barangay_id === $actor->barangay_id;
    }

    public function managePolice(User $actor, User $target): bool
    {
        return $target->role === Role::BarangayPolice && $target->barangay_id === $actor->barangay_id;
    }

    /** Citywide admin managing another staff account (secretary/vawc/admin). */
    public function manageStaffAccount(User $actor, User $target): bool
    {
        return $target->role?->isStaff() ?? false;
    }

    /** Citywide admin deleting a staff account — manageable role, and never themselves. */
    public function delete(User $actor, User $target): bool
    {
        return $this->manageStaffAccount($actor, $target) && $actor->id !== $target->id;
    }
}
