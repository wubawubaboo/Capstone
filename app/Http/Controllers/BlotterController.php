<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlotterController extends Controller
{
    public function index()
    {
    $blotters = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
        ->whereDoesntHave('vawcDetail') // Strict data isolation
        ->with(['report.user', 'receiver'])
        ->latest()
        ->paginate(10);

    return Inertia::render('Secretary/BlotterManagement', [
        'blotters' => $blotters
    ]);
    }

    public function create()
    {
        $barangayId = Auth::user()->barangay_id;

        $pendingReports = Report::with('user')
            ->whereDoesntHave('blotter')
            ->where('status', 'Pending')
            ->get();

        $residents = User::where('barangay_id', $barangayId)->get();

        return Inertia::render('Secretary/CreateBlotter', [
            'pendingReports' => $pendingReports,
            'residents' => $residents
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id'      => 'nullable|exists:reports,id',
            
            'complainant_id' => 'nullable|required_without:report_id|exists:users,id',
            'incident_type'  => 'nullable|required_without:report_id|string|max:255',
            'description'    => 'nullable|required_without:report_id|string',
            
            'is_registered_respondent' => 'required|boolean',
            
            'receiver_id'    => 'nullable|required_if:is_registered_respondent,true,1|exists:users,id',
            'receiver_name'  => 'nullable|required_if:is_registered_respondent,false,0|string|max:255',
        ]);

        $barangayId = Auth::user()->barangay_id;

        $complainantId = null;
        $complainantName = null;
        $incidentType = null;
        $incidentDescription = null;
        $reportId = null;

        if (!empty($validated['report_id'])) {
            $report = Report::with('user')->findOrFail($validated['report_id']);
            $report->update(['status' => 'Blottered']);
            
            $reportId = $report->id;
            $complainantId = $report->user_id;
            $complainantName = $report->user ? $report->user->full_name : 'Unknown';
            $incidentType = $report->incident_type;
            $incidentDescription = $report->description;
        } else {
            $complainantId = $validated['complainant_id'];
            $user = User::find($complainantId);
            
            $complainantName = $user ? $user->full_name : 'Unknown';
            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        $caseNumber = 'BLT-' . date('Y') . '-' . str_pad(BlotterRecord::count() + 1, 4, '0', STR_PAD_LEFT);

        $blotter = BlotterRecord::create([
            'barangay_id'          => $barangayId,
            'report_id'            => $reportId,
            'complainant_id'       => $complainantId,
            'complainant_name'     => $complainantName,
            'incident_type'        => $incidentType,
            'incident_description' => $incidentDescription,
            'receiver_id'          => $validated['is_registered_respondent'] ? $validated['receiver_id'] : null,
            'receiver_name'        => !$validated['is_registered_respondent'] ? $validated['receiver_name'] : null,
            'case_number'          => $caseNumber,
            'status'               => 'Pending',
            'official_entry_date'  => now(),
        ]);

        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'Blotter', "Created Blotter Case #{$caseNumber}.");

        return to_route('secretary.blotters')->with('success', 'Blotter record created successfully.');
    }

    public function scheduleMediation(Request $request, $id)
    {
    $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
        ->whereDoesntHave('vawcDetail')
        ->findOrFail($id);

    $validated = $request->validate([
        'scheduled_date' => 'required|date|after:now',
        'status'         => 'nullable|string|max:50',
    ]);

    $meetingCount = MediationSchedule::where('blotter_record_id', $blotter->id)->count();

    if ($meetingCount >= 3) {
        return back()->withErrors(['error' => 'Maximum 3 mediation sessions reached for this case.']);
    }

    $schedule = MediationSchedule::create([
        'blotter_record_id' => $blotter->id,
        'meeting_number'    => $meetingCount + 1,
        'scheduled_date'    => $validated['scheduled_date'],
        'status'            => $validated['status'] ?? 'Scheduled',
    ]);

    $blotter->update(['status' => 'Under Mediation']);

    SystemLog::logAction(
        Auth::user()->barangay_id,
        Auth::id(),
        'CREATE',
        'Mediation Schedule',
        "Scheduled Session #{$schedule->meeting_number} for case #{$blotter->case_number}."
    );

    return back()->with('success', "Mediation session #{$schedule->meeting_number} scheduled successfully.");
    }


    public function caseHistory($id)
    {
        $blotter = BlotterRecord::with(['report.user', 'receiver', 'vawcDetail', 'mediations'])
            ->findOrFail($id);

        return Inertia::render('Secretary/CaseHistory', [
            'blotter' => $blotter
        ]);
    }

    public function mediationCalendar()
    {
        $barangayId = Auth::user()->barangay_id;

        $schedules = MediationSchedule::whereHas('blotter', function ($query) use ($barangayId) {
                $query->where('barangay_id', $barangayId)
                      ->whereDoesntHave('vawcDetail');
            })
            ->with(['blotter.report.user', 'blotter.receiver'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return Inertia::render('Secretary/MediationCalendar', [
            'schedules' => $schedules
        ]);
    }

    public function mediationMeetingDetails($id)
    {
        $blotter = BlotterRecord::with(['report.user', 'receiver', 'vawcDetail', 'mediations'])
            ->findOrFail($id);

        $latestMediation = $blotter->mediations->sortByDesc('scheduled_date')->first();
        $blotter->scheduled_date = $latestMediation ? $latestMediation->scheduled_date : null;

        return Inertia::render('Secretary/MediationMeetingDetails', [
            'blotter' => $blotter
        ]);
    }
}