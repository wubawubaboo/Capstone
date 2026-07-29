<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\Report;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlotterController extends Controller
{
    public function index()
    {
        $blotters = BlotterRecord::with(['report', 'receiver'])->latest()->paginate(15);
        return Inertia::render('Secretary/BlotterManagement', compact('blotters')); 
    }

    public function create()
    {
        $barangayId = Auth::user()->barangay_id;

        // Fetch pending reports that haven't been turned into a blotter yet
        $pendingReports = Report::with('user')
            ->whereDoesntHave('blotter')
            ->where('status', 'Pending')
            ->get();

        // Fetch users in this barangay to select as walk-in complainants or respondents
        $residents = \App\Models\User::where('barangay_id', $barangayId)->get();

        return Inertia::render('Secretary/CreateBlotter', [
            'pendingReports' => $pendingReports,
            'residents' => $residents
        ]);
    }

    /**
     * Store a newly created Blotter Record in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id'      => 'nullable|exists:reports,id',
            'complainant_id' => 'required_without:report_id|exists:users,id',
            'incident_type'  => 'required_without:report_id|string|max:255',
            'description'    => 'required_without:report_id|string',
            
            'is_registered_respondent' => 'required|boolean',
            'receiver_id'    => 'nullable|required_if:is_registered_respondent,true|exists:users,id',
            'receiver_name'  => 'nullable|required_if:is_registered_respondent,false|string|max:255',
        ]);

        $barangayId = Auth::user()->barangay_id;

        if (empty($validated['report_id'])) {
            $report = Report::create([
                'user_id'       => $validated['complainant_id'],
                'incident_type' => $validated['incident_type'],
                'description'   => $validated['description'],
                'status'        => 'Blottered', 
            ]);
            $reportId = $report->id;
        } else {
            $reportId = $validated['report_id'];
            Report::where('id', $reportId)->update(['status' => 'Blottered']);
        }

        // 2. Generate a Case Number
        $caseNumber = 'BLT-' . date('Y') . '-' . str_pad(BlotterRecord::count() + 1, 4, '0', STR_PAD_LEFT);

        // 3. Create the Blotter Record (Handling the Nullable Logic)
        $blotter = BlotterRecord::create([
            'barangay_id'         => $barangayId,
            'report_id'           => $reportId,
            'receiver_id'         => $validated['is_registered_respondent'] ? $validated['receiver_id'] : null,
            'receiver_name'       => !$validated['is_registered_respondent'] ? $validated['receiver_name'] : null,
            'case_number'         => $caseNumber,
            'status'              => 'Pending',
            'official_entry_date' => now(),
        ]);

        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'Blotter', "Created Blotter Case #{$caseNumber}.");

        return redirect()->route('secretary.blotters')->with('success', 'Blotter record created successfully.');
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

    public function caseHistory()
    {
        $cases = BlotterRecord::with(['report', 'receiver'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Secretary/CaseHistory', [
            'cases' => $cases
        ]);
    }
    public function mediationCalendar()
    {
        // 1. Query the MediationSchedule model directly since that's where the dates live
        // 2. Eager load the related blotter, report, and user
        $schedules = \App\Models\MediationSchedule::with(['blotter.report.user'])
            ->whereNotNull('scheduled_date')
            ->orderBy('scheduled_date', 'asc')
            ->get()
            ->map(function ($mediation) {
                // 3. Format the output to exactly match what the React component expects
                return [
                    'id' => $mediation->blotter_id, // We pass the Blotter ID for the clickable links
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