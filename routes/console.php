<?php

// routes/console.php

use Illuminate\Support\Facades\Schedule;
use App\Models\MediationSchedule;
use App\Services\PhilSmsService;
use Carbon\Carbon;

Schedule::call(function (PhilSmsService $smsService) {
    $targetDate = Carbon::now()->addDays(2)->toDateString();

    $hearings = MediationSchedule::with(['blotter.barangay'])
        ->whereDate('scheduled_date', $targetDate)
        ->where('status', 'scheduled') 
        ->get();

    foreach ($hearings as $hearing) {
        $barangayName = $hearing->blotter->barangay->name ?? 'San Nicolas';
        $time = Carbon::parse($hearing->scheduled_date)->format('h:i A');
        
        $message = "Brgy. {$barangayName} Reminder: You have a scheduled mediation hearing on " . 
                   Carbon::parse($targetDate)->format('M d, Y') . " at {$time}. " .
                   "Please be present at the Barangay Hall.";

        if ($hearing->blotter->complainant_phone) {
            $smsService->sendSms($hearing->blotter->complainant_phone, $message);
        }

        if ($hearing->blotter->respondent_phone) {
            $smsService->sendSms($hearing->blotter->respondent_phone, $message);
        }
    }
})->dailyAt('08:00');