<?php

namespace App\Actions\Account;

use App\Jobs\SendSmsJob;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectResidentAccount
{
    public function __invoke(User $user, string $reason, ?string $customMessage): void
    {
        $message = "Your account verification was declined. Reason: {$reason}.";
        if (!empty($customMessage)) {
            $message .= " {$customMessage}";
        }
        $message .= ' Please register again with valid information.';

        DB::transaction(function () use ($user, $reason) {
            SystemLog::record('DELETE', 'Account', "Rejected and removed resident account for {$user->full_name}. Reason: {$reason}.", $user->barangay_id);
            $user->delete();
        });

        $user->deleteVerificationDocuments();

        SendSmsJob::dispatch($user->phone_number, $message);
    }
}
