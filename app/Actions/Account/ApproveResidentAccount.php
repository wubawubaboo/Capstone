<?php

namespace App\Actions\Account;

use App\Jobs\SendSmsJob;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveResidentAccount
{
    public function __invoke(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update(['is_verified' => true]);

            SystemLog::record('UPDATE', 'Account', "Approved resident account for {$user->full_name}.", $user->barangay_id);
        });

        SendSmsJob::dispatch(
            $user->phone_number,
            'Your account has been approved. You may now log in to the portal and access our services.'
        );
    }
}
