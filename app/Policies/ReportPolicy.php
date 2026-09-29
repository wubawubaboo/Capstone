<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        if ($user->id === $report->user_id) {
            return true;
        }

        return $this->handledByUsersDesk($user, $report);
    }

    public function update(User $user, Report $report): bool
    {
        return $user->id !== $report->user_id && $this->handledByUsersDesk($user, $report);
    }

    private function handledByUsersDesk(User $user, Report $report): bool
    {
        return $report->barangay_id !== null
            && $report->barangay_id === $user->barangay_id
            && $report->isHandledBy($user->role);
    }
}
