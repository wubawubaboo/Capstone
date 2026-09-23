<?php

namespace App\Actions\Account;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\PhilSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RejectResidentAccount
{
    public function __construct(private PhilSmsService $smsService)
    {
    }

    public function __invoke(User $user, string $reason, ?string $customMessage): void
    {
        $message = "Your account verification was declined. Reason: {$reason}.";
        if (!empty($customMessage)) {
            $message .= " {$customMessage}";
        }
        $message .= ' Please register again with valid information.';

        $this->smsService->sendSms($user->phone_number, $message);

        $idPhotoPath = $user->id_photo_path;
        $selfiePath = $user->selfie_id_path;

        DB::transaction(function () use ($user, $reason) {
            SystemLog::record('DELETE', 'Account', "Rejected and removed resident account for {$user->full_name}. Reason: {$reason}.", $user->barangay_id);
            $user->delete();
        });

        // File deletion isn't transactional; do it only after the DB write
        // commits so a failed delete leaves orphaned files (cheap to clean
        // up later) rather than a deleted user with no removed evidence.
        if ($idPhotoPath) {
            Storage::delete($idPhotoPath);
        }
        if ($selfiePath) {
            Storage::delete($selfiePath);
        }
    }
}
