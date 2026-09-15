<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\Report;
use App\Models\VawcDetail;
use App\Models\User;
use App\Models\MediationSchedule;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class VawcController extends Controller
{
    public function index()
    {
        $vawcBlotters = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->with(['report.user', 'receiver', 'vawcDetail'])
            ->latest()
            ->paginate(10);

        return Inertia::render('VAWC/BlotterManagement', [
            'blotters' => $vawcBlotters,
        ]);
    }

    public function create(Request $request)
    {
        // MODIFIED: Included 'address', 'date_of_birth', and 'start_of_residency'
        // to ensure the frontend receives these new fields for context.
        $residents = User::where('barangay_id', Auth::user()->barangay_id)
            ->where('role', 'resident')
            ->select('id', 'full_name', 'phone_number', 'address', 'date_of_birth', 'start_of_residency')
            ->get();

        $pendingReports = Report::with('user')
            ->whereDoesntHave('blotter')
            ->whereIn('status', ['Pending', 'pending', 'in_progress'])
            ->get();

        return Inertia::render('VAWC/CreateBlotter', [
            'residents' => $residents,
            'pendingReports' => $pendingReports,
            'selectedReportId' => $request->query('report_id', ''),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id'                 => 'nullable|exists:reports,id',

            'is_registered_complainant' => 'required|boolean',
            'complainant_id'            => 'nullable|required_if:is_registered_complainant,true,1|exists:users,id',
            'complainant_name'          => 'nullable|required_if:is_registered_complainant,false,0|string|max:255',

            'is_registered_respondent'  => 'required|boolean',
            'receiver_id'               => 'nullable|required_if:is_registered_respondent,true,1|exists:users,id',
            'receiver_name'             => 'nullable|required_if:is_registered_respondent,false,0|string|max:255',

            'incident_type'             => 'nullable|required_without:report_id|string|max:255',
            'description'               => 'nullable|required_without:report_id|string',
            'confidential_notes'        => 'nullable|string',
        ]);

        $barangayId = Auth::user()->barangay_id;

        $reportId = null;
        $complainantId = null;
        $complainantName = null;
        $incidentType = null;
        $incidentDescription = null;

        if (!empty($validated['report_id'])) {
            $report = Report::with('user')->findOrFail($validated['report_id']);
            $report->update(['status' => 'Blottered']);

            $reportId = $report->id;
            $complainantId = $report->user_id;
            $complainantName = $report->user ? $report->user->full_name : 'Unknown';
            $incidentType = $report->incident_type;
            $incidentDescription = $report->description;
        } else {
            $complainantId = $validated['is_registered_complainant'] ? $validated['complainant_id'] : null;

            if ($complainantId) {
                $user = User::find($complainantId);
                $complainantName = $user ? $user->full_name : null;
            } else {
                $complainantName = $validated['complainant_name'];
            }

            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        $caseNumber = 'VAWC-' . date('Y') . '-' . str_pad(BlotterRecord::where('barangay_id', $barangayId)->count() + 1, 4, '0', STR_PAD_LEFT);

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

        // Create linked confidential VAWC details
        VawcDetail::create([
            'blotter_record_id'    => $blotter->id,
            'officer_in_charge_id' => Auth::id(),
            'confidential_notes'   => !empty($validated['confidential_notes']) ? $validated['confidential_notes'] : $incidentDescription,
        ]);

        // Audit Trail
        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'VAWC Blotter', "Recorded confidential VAWC case #{$caseNumber}.");

        return redirect()->route('vawc.blotters')->with('success', 'VAWC incident record created successfully.');
    }

    public function analytics()
    {
        $barangayId = Auth::user()->barangay_id;

        $totalVawcCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->count();

        $settledCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->where('status', 'Settled')
            ->count();

        $escalatedCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->where('status', 'Escalated')
            ->count();

        $incidentDistribution = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->select('incident_type as name', DB::raw('count(*) as value'))
            ->groupBy('incident_type')
            ->get();

        $caseStatusData = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->select('status as name', DB::raw('count(*) as value'))
            ->groupBy('status')
            ->get();

        return Inertia::render('VAWC/Analytics', [
            'totalVawcCases'       => $totalVawcCases,
            'settledCases'         => $settledCases,
            'escalatedCases'       => $escalatedCases,
            'incidentDistribution' => $incidentDistribution,
            'caseStatusData'       => $caseStatusData,
        ]);
    }

    public function caseHistory($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->with(['report.user', 'receiver', 'vawcDetail', 'mediations'])
            ->findOrFail($id);

        return Inertia::render('VAWC/CaseHistory', [
            'blotter' => $blotter,
        ]);
    }

    public function mediationCalendar()
    {
        $schedules = MediationSchedule::whereHas('blotter', function ($query) {
                $query->where('barangay_id', Auth::user()->barangay_id)
                      ->whereHas('vawcDetail');
            })
            ->with(['blotter.report.user', 'blotter.receiver'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return Inertia::render('VAWC/MediationCalendar', [
            'schedules' => $schedules,
        ]);
    }

    public function scheduleMediation(Request $request, $id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
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
            'VAWC Mediation Schedule',
            "Scheduled confidential Session #{$schedule->meeting_number} for case #{$blotter->case_number}."
        );

        return back()->with('success', "VAWC mediation session #{$schedule->meeting_number} scheduled successfully.");
    }

    public function updateMediationNotes(Request $request, $id)
    {
        $mediation = MediationSchedule::whereHas('blotter', function ($query) {
                $query->where('barangay_id', Auth::user()->barangay_id)
                    ->whereHas('vawcDetail');
            })
            ->findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:10000',
        ]);

        $mediation->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'VAWC Mediation Schedule',
            "Updated notes for Session #{$mediation->meeting_number} of case #{$mediation->blotter->case_number}."
        );

        return back()->with('success', 'Mediation meeting notes saved successfully.');
    }

    public function resolveCase($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->findOrFail($id);

        $blotter->update(['status' => 'Resolved']);

        if ($blotter->report_id) {
            $report = Report::find($blotter->report_id);
            if ($report) {
                $report->update(['status' => 'completed']);
            }
        }

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'VAWC Blotter',
            "Resolved VAWC Case #{$blotter->case_number}."
        );

        return redirect()->route('vawc.blotters')->with('success', 'Case resolved and report closed.');
    }

    public function escalateCase($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->findOrFail($id);

        $blotter->update(['status' => 'Escalated to Court']);

        if ($blotter->report_id) {
            $report = Report::find($blotter->report_id);
            if ($report) {
                $report->update(['status' => 'escalated']);
            }
        }

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'VAWC Blotter',
            "Escalated VAWC Case #{$blotter->case_number} to Court."
        );

        return redirect()->route('vawc.blotters')->with('success', 'Case escalated to court.');
    }
}