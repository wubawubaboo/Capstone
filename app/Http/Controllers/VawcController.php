<?php

namespace App\Http\Controllers;

use App\Enums\BlotterStatus;
use App\Http\Requests\Blotter\ReopenCaseRequest;
use App\Http\Requests\Blotter\ScheduleMediationRequest;
use App\Http\Requests\Blotter\UpdateMediationNotesRequest;
use App\Http\Requests\Vawc\StoreVawcBlotterRequest;
use App\Models\BlotterRecord;
use App\Models\Report;
use App\Models\VawcDetail;
use App\Models\User;
use App\Models\MediationSchedule;
use App\Models\SystemLog;
use Barryvdh\DomPDF\Facade\Pdf;
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
            ->paginate(self::PER_PAGE);

        return Inertia::render('VAWC/BlotterManagement', [
            'blotters' => $vawcBlotters,
        ]);
    }

    public function create(Request $request)
    {
        $pendingReports = Report::with('user')
            ->whereDoesntHave('blotter')
            ->whereIn('status', ['Pending', 'pending', 'in_progress'])
            ->whereHas('user', fn ($q) => $q->where('barangay_id', Auth::user()->barangay_id))
            ->get();

        return Inertia::render('VAWC/CreateBlotter', [
            'pendingReports' => $pendingReports,
            'selectedReportId' => $request->query('report_id', ''),
        ]);
    }

    public function searchResidents(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $residents = User::where('barangay_id', Auth::user()->barangay_id)
            ->where('role', 'resident')
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                    ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'phone_number', 'address']);

        return response()->json($residents);
    }

    public function store(StoreVawcBlotterRequest $request)
    {
        $validated = $request->validated();

        $barangayId = Auth::user()->barangay_id;

        $reportId = null;
        $complainantId = null;
        $complainantName = null;
        $incidentType = null;
        $incidentDescription = null;

        if (!empty($validated['report_id'])) {
            $report = Report::with('user')
                ->whereHas('user', fn ($q) => $q->where('barangay_id', $barangayId))
                ->findOrFail($validated['report_id']);
            $report->update(['status' => 'Blottered']);

            $reportId = $report->id;
            $complainantId = $report->user_id;
            $complainantName = $report->user ? $report->user->full_name : 'Unknown';
            $incidentType = $report->incident_type;
            $incidentDescription = $report->description;
        } else {
            $complainantId = $validated['is_registered_complainant'] ? $validated['complainant_id'] : null;

            if ($complainantId) {
                $user = User::where('barangay_id', $barangayId)->find($complainantId);
                abort_unless($user, 422, 'Complainant must be a resident of your barangay.');
                $complainantName = $user->full_name;
            } else {
                $complainantName = $validated['complainant_name'];
            }

            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        if ($validated['is_registered_respondent']) {
            $respondentExists = User::where('barangay_id', $barangayId)->where('id', $validated['receiver_id'])->exists();
            abort_unless($respondentExists, 422, 'Respondent must be a resident of your barangay.');
        }

        $blotter = DB::transaction(function () use ($barangayId, $reportId, $complainantId, $complainantName, $incidentType, $incidentDescription, $validated) {
            $caseNumber = BlotterRecord::generateCaseNumber('VAWC', $barangayId);

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
                'status'               => BlotterStatus::Pending,
                'official_entry_date'  => now(),
            ]);

            $blotter->statusHistory()->create([
                'from_status' => null,
                'to_status'   => BlotterStatus::Pending->value,
                'changed_by'  => Auth::id(),
                'note'        => 'Confidential VAWC case filed.',
            ]);

            // Create linked confidential VAWC details
            VawcDetail::create([
                'blotter_record_id'    => $blotter->id,
                'officer_in_charge_id' => Auth::id(),
                'confidential_notes'   => !empty($validated['confidential_notes']) ? $validated['confidential_notes'] : $incidentDescription,
            ]);

            return $blotter;
        });

        // Audit Trail
        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'VAWC Blotter', "Recorded confidential VAWC case #{$blotter->case_number}.");

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
            ->where('status', BlotterStatus::Resolved)
            ->count();

        $escalatedCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->where('status', BlotterStatus::EscalatedToCourt)
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
            ->with(['report.user', 'receiver', 'vawcDetail', 'mediations', 'statusHistory.actor'])
            ->findOrFail($id);

        return Inertia::render('VAWC/CaseHistory', [
            'blotter' => $blotter,
        ]);
    }

    public function downloadCaseReport($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->with(['barangay', 'report.user', 'receiver', 'vawcDetail.officer', 'mediations'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.case-report', [
            'blotter'     => $blotter,
            'generatedBy' => Auth::user()->full_name,
            'generatedAt' => now(),
        ]);

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'EXPORT',
            'Blotter',
            "Generated confidential VAWC case report PDF for case #{$blotter->case_number}."
        );

        return $pdf->download("vawc-case-report-{$blotter->case_number}.pdf");
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

    public function scheduleMediation(ScheduleMediationRequest $request, $id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->findOrFail($id);

        $validated = $request->validated();

        $meetingCount = MediationSchedule::where('blotter_record_id', $blotter->id)->count();

        if ($meetingCount >= 3) {
            return back()->withErrors(['error' => 'Maximum 3 mediation sessions reached for this case.']);
        }

        try {
            $schedule = DB::transaction(function () use ($blotter, $validated, $meetingCount) {
                $schedule = MediationSchedule::create([
                    'blotter_record_id' => $blotter->id,
                    'meeting_number'    => $meetingCount + 1,
                    'scheduled_date'    => $validated['scheduled_date'],
                    'status'            => $validated['status'] ?? 'Scheduled',
                ]);

                $blotter->transitionStatus(
                    BlotterStatus::UnderMediation,
                    Auth::user(),
                    "Confidential mediation session #{$schedule->meeting_number} scheduled."
                );

                return $schedule;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'CREATE',
            'VAWC Mediation Schedule',
            "Scheduled confidential Session #{$schedule->meeting_number} for case #{$blotter->case_number}."
        );

        return back()->with('success', "VAWC mediation session #{$schedule->meeting_number} scheduled successfully.");
    }

    public function updateMediationNotes(UpdateMediationNotesRequest $request, $id)
    {
        $mediation = MediationSchedule::whereHas('blotter', function ($query) {
                $query->where('barangay_id', Auth::user()->barangay_id)
                    ->whereHas('vawcDetail');
            })
            ->findOrFail($id);

        $validated = $request->validated();

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

        try {
            DB::transaction(function () use ($blotter) {
                $blotter->transitionStatus(BlotterStatus::Resolved, Auth::user(), 'Confidential VAWC case resolved.');

                if ($blotter->report_id) {
                    $report = Report::find($blotter->report_id);
                    if ($report) {
                        $report->update(['status' => 'completed']);
                    }
                }
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
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

        try {
            DB::transaction(function () use ($blotter) {
                $blotter->transitionStatus(BlotterStatus::EscalatedToCourt, Auth::user(), 'Confidential VAWC case escalated to court.');

                if ($blotter->report_id) {
                    $report = Report::find($blotter->report_id);
                    if ($report) {
                        $report->update(['status' => 'escalated']);
                    }
                }
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
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

    public function reopenCase(ReopenCaseRequest $request, $id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->findOrFail($id);

        $validated = $request->validated();

        try {
            $blotter->reopen(BlotterStatus::Pending, Auth::user(), $validated['reason']);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'VAWC Blotter',
            "Reopened VAWC Case #{$blotter->case_number}: {$validated['reason']}"
        );

        return back()->with('success', 'Case reopened successfully.');
    }
}