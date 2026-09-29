<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\BlotterRecord;
use App\Models\User;

class BlotterRecordPolicy
{
    /**
     * Whether $user may access this specific case: same barangay, and on
     * the desk that owns this case type (VAWC-flagged cases are only
     * visible to the vawc desk; everything else only to the secretary desk).
     * This is the single place the VAWC-confidentiality boundary is
     * enforced — every blotter-scoped controller action should route
     * through this instead of re-implementing the scoped query.
     */
    public function view(User $user, BlotterRecord $blotter): bool
    {
        if ($user->barangay_id !== $blotter->barangay_id) {
            return false;
        }

        return $user->role === ($blotter->isVawcCase() ? Role::Vawc : Role::Secretary);
    }
}
