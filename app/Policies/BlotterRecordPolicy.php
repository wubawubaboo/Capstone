<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\BlotterRecord;
use App\Models\User;

class BlotterRecordPolicy
{
    public function view(User $user, BlotterRecord $blotter): bool
    {
        if ($user->barangay_id !== $blotter->barangay_id) {
            return false;
        }

        return $user->role === ($blotter->isVawcCase() ? Role::Vawc : Role::Secretary);
    }
}
