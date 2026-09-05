<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\MediationSchedule;
use App\Services\PhilSmsService;
use Carbon\Carbon;

Schedule::call(function (PhilSmsService $smsService) {
    // Get the date exactly 2 days from today
    $targetDate = Carbon::now()->addDays(2)->toDateString();

    // Find all scheduled hearings for that specific date
    $hearings = MediationSchedule::whereDate('schedule_date', $targetDate)
        ->where('status', 'scheduled') // Adjust status column as needed
        ->get();

    foreach ($hearings as $hearing) {
        $time = Carbon::parse($hearing->schedule_date)->format('h:i A');
        $message = "Brgy. San Nicolas Reminder: You have a scheduled mediation hearing on " . 
                   Carbon::parse($targetDate)->format('M d, Y') . " at {$time}. " .
                   "Please be present at the Barangay Hall.";

        if ($hearing->complainant_phone) {
            $smsService->sendSms($hearing->complainant_phone, $message);
        }

        if ($hearing->respondent_phone) {
            $smsService->sendSms($hearing->respondent_phone, $message);
        }
    }
})->dailyAt('08:00');