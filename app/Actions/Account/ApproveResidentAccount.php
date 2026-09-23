<?php

namespace App\Actions\Account;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\PhilSmsService;
use Illuminate\Support\Facades\DB;

class ApproveResidentAccount
{
    public function __construct(private PhilSmsService $smsService)
    {
    }

    public function __invoke(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update(['is_verified' => true]);

            SystemLog::record('UPDATE', 'Account', "Approved resident account for {$user->full_name}.", $user->barangay_id);
        });

        $this->smsService->sendSms(
            $user->phone_number,
            'Your account has been approved. You may now log in to the portal and access our services.'
        );
    }
}
