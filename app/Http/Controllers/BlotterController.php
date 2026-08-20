<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
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
            // Case 1: Convert an existing incident report to a blotter
            $report = Report::with('user')->findOrFail($validated['report_id']);
            $report->update(['status' => 'Blottered']);
            
            $reportId = $report->id;
            $complainantId = $report->user_id;
            $complainantName = $report->user ? $report->user->full_name : 'Unknown';
            $incidentType = $report->incident_type;
            $incidentDescription = $report->description;
        } else {
            // Case 2: Walk-in complaint (no prior report required)
            $complainantId = $validated['complainant_id'];
            $user = User::find($complainantId);
            
            $complainantName = $user ? $user->full_name : 'Unknown';
            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        // Generate a standard Case Number
        $caseNumber = 'BLT-' . date('Y') . '-' . str_pad(BlotterRecord::count() + 1, 4, '0', STR_PAD_LEFT);

        // Create the Blotter Record with the new redundant safety fields
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

    public function scheduleMediation(Request $request, BlotterRecord $blotter)
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date|after:today',
            'meeting_number' => 'required|integer|min:1'
        ]);

        $blotter->scheduleMediation($validated['scheduled_date'], $validated['meeting_number']);

        SystemLog::logAction($blotter->barangay_id, Auth::id(), 'SCHEDULE', 'Mediation', "Scheduled meeting #{$validated['meeting_number']} for Case #{$blotter->case_number}.");

        return back()->with('success', 'Mediation scheduled successfully.');
    }

    public function storeVawcDetail(Request $request, BlotterRecord $blotter)
    {
        $validated = $request->validate([
            'confidential_notes' => 'required|string'
        ]);

        $blotter->vawcDetail()->create([
            'officer_in_charge_id' => Auth::id(),
            'confidential_notes' => $validated['confidential_notes']
        ]);

        return back()->with('success', 'VAWC details attached securely.');
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
        $schedules = \App\Models\MediationSchedule::with(['blotter.report.user'])
            ->whereNotNull('scheduled_date')
            ->orderBy('scheduled_date', 'asc')
            ->get()
            ->map(function ($mediation) {
                return [
                    'id' => $mediation->blotter_id,
                    'scheduled_date' => $mediation->scheduled_date,
                    'report' => $mediation->blotter ? $mediation->blotter->report : null,
                ];
            });

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