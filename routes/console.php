<?php

// routes/console.php

use App\Enums\MediationStatus;
use App\Jobs\SendSmsJob;
use App\Models\MediationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;

// Two days before each mediation hearing, remind the complainant by SMS.
// Only registered complainants have a phone number on file; respondents are
// recorded by name only, so they can't be reminded this way.
Schedule::call(function () {
    $targetDate = Carbon::now()->addDays(2)->toDateString();

    $hearings = MediationSchedule::with(['blotter.barangay', 'blotter.complainant'])
        ->whereDate('scheduled_date', $targetDate)
        ->where('status', MediationStatus::Scheduled)
        ->get();

    foreach ($hearings as $hearing) {
        $phone = $hearing->blotter->complainant?->phone_number;

        if (!$phone) {
            continue;
        }

        $barangayName = $hearing->blotter->barangay->name ?? 'San Nicolas';
        $time = Carbon::parse($hearing->scheduled_date)->format('h:i A');

        $message = "Brgy. {$barangayName} Reminder: You have a scheduled mediation hearing on " .
                   Carbon::parse($targetDate)->format('M d, Y') . " at {$time}. " .
                   "Please be present at the Barangay Hall.";

        SendSmsJob::dispatch($phone, $message);
    }
})->name('mediation-hearing-reminders')->dailyAt('08:00');
