<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Secretary viewing a resident's ID/selfie verification uploads. */
    public function viewVerificationDocuments(User $actor, User $target): bool
    {
        return $actor->role === 'secretary'
            && $target->role === 'resident'
            && $target->barangay_id === $actor->barangay_id;
    }

    /** Secretary managing (update/destroy) a resident account in their own barangay. */
    public function manageResident(User $actor, User $target): bool
    {
        return $target->role === 'resident' && $target->barangay_id === $actor->barangay_id;
    }

    /** Secretary managing (update/destroy) a barangay_police account in their own barangay. */
    public function managePolice(User $actor, User $target): bool
    {
        return $target->role === 'barangay_police' && $target->barangay_id === $actor->barangay_id;
    }

    /** Citywide admin managing another staff account (secretary/vawc/admin). */
    public function manageStaffAccount(User $actor, User $target): bool
    {
        return in_array($target->role, User::MANAGEABLE_STAFF_ROLES, true);
    }

    /** Citywide admin deleting a staff account — manageable role, and never themselves. */
    public function delete(User $actor, User $target): bool
    {
        return $this->manageStaffAccount($actor, $target) && $actor->id !== $target->id;
    }
}
