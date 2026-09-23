<?php

namespace App\Http\Controllers;

use App\Actions\Vawc\FileVawcCase;
use App\Enums\BlotterStatus;
use App\Enums\MediationStatus;
use App\Enums\ReportStatus;
use App\Http\Requests\Blotter\ReopenCaseRequest;
use App\Http\Requests\Blotter\ScheduleMediationRequest;
use App\Http\Requests\Blotter\UpdateMediationNotesRequest;
use App\Http\Requests\Vawc\StoreVawcBlotterRequest;
use App\Models\BlotterRecord;
use App\Models\Report;
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
        $vawcBlotters = BlotterRecord::forBarangay(Auth::user()->barangay_id)
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
            ->whereIn('status', [ReportStatus::Pending, ReportStatus::InProgress])
            ->forBarangay(Auth::user()->barangay_id)
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

    public function store(StoreVawcBlotterRequest $request, FileVawcCase $fileVawcCase)
    {
        $barangayId = Auth::user()->barangay_id;

        $blotter = $fileVawcCase($request->validated(), $barangayId, $request->user());

        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'VAWC Blotter', "Recorded confidential VAWC case #{$blotter->case_number}.");

        return redirect()->route('vawc.blotters')->with('success', 'VAWC incident record created successfully.');
    }

    public function analytics()
    {
        $barangayId = Auth::user()->barangay_id;

        $totalVawcCases = BlotterRecord::forBarangay($barangayId)
            ->whereHas('vawcDetail')
            ->count();

        $settledCases = BlotterRecord::forBarangay($barangayId)
            ->whereHas('vawcDetail')
            ->resolved()
            ->count();

        $escalatedCases = BlotterRecord::forBarangay($barangayId)
            ->whereHas('vawcDetail')
            ->escalatedToCourt()
            ->count();

        $incidentDistribution = BlotterRecord::forBarangay($barangayId)
            ->whereHas('vawcDetail')
            ->select('incident_type as name', DB::raw('count(*) as value'))
            ->groupBy('incident_type')
            ->get();

        $caseStatusData = BlotterRecord::forBarangay($barangayId)
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

    public function caseHistory(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $blotter->load(['report.user', 'receiver', 'vawcDetail', 'mediations', 'statusHistory.actor']);

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'VIEW',
            'VAWC Blotter',
            "Viewed confidential VAWC case #{$blotter->case_number}."
        );

        return Inertia::render('VAWC/CaseHistory', [
            'blotter' => $blotter,
        ]);
    }

    public function downloadCaseReport(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $blotter->load(['barangay', 'report.user', 'receiver', 'vawcDetail.officer', 'mediations']);

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

    public function scheduleMediation(ScheduleMediationRequest $request, BlotterRecord $blotter)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $validated = $request->validated();

        $meetingCount = MediationSchedule::where('blotter_record_id', $blotter->id)->count();

        if ($meetingCount >= 3) {
            return back()->withErrors(['error' => 'Maximum 3 mediation sessions reached for this case.']);
        }

        $schedule = DB::transaction(function () use ($blotter, $validated, $meetingCount) {
            $schedule = MediationSchedule::create([
                'blotter_record_id' => $blotter->id,
                'meeting_number'    => $meetingCount + 1,
                'scheduled_date'    => $validated['scheduled_date'],
                'status'            => $validated['status'] ?? MediationStatus::Scheduled,
            ]);

            $blotter->transitionStatus(
                BlotterStatus::UnderMediation,
                Auth::user(),
                "Confidential mediation session #{$schedule->meeting_number} scheduled."
            );

            return $schedule;
        });

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'CREATE',
            'VAWC Mediation Schedule',
            "Scheduled confidential Session #{$schedule->meeting_number} for case #{$blotter->case_number}."
        );

        return back()->with('success', "VAWC mediation session #{$schedule->meeting_number} scheduled successfully.");
    }

    public function updateMediationNotes(UpdateMediationNotesRequest $request, MediationSchedule $mediation)
    {
        abort_unless($request->user()->can('view', $mediation->blotter), 404);

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

    public function resolveCase(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        DB::transaction(function () use ($blotter) {
            $blotter->transitionStatus(BlotterStatus::Resolved, Auth::user(), 'Confidential VAWC case resolved.');

            if ($blotter->report_id) {
                $report = Report::find($blotter->report_id);
                if ($report) {
                    $report->transitionStatus(ReportStatus::Completed, Auth::user(), 'Linked VAWC case resolved.');
                }
            }
        });

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'VAWC Blotter',
            "Resolved VAWC Case #{$blotter->case_number}."
        );

        return redirect()->route('vawc.blotters')->with('success', 'Case resolved and report closed.');
    }

    public function escalateCase(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        DB::transaction(function () use ($blotter) {
            $blotter->transitionStatus(BlotterStatus::EscalatedToCourt, Auth::user(), 'Confidential VAWC case escalated to court.');

            if ($blotter->report_id) {
                $report = Report::find($blotter->report_id);
                if ($report) {
                    $report->transitionStatus(ReportStatus::Escalated, Auth::user(), 'Linked VAWC case escalated to court.');
                }
            }
        });

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'VAWC Blotter',
            "Escalated VAWC Case #{$blotter->case_number} to Court."
        );

        return redirect()->route('vawc.blotters')->with('success', 'Case escalated to court.');
    }

    public function reopenCase(ReopenCaseRequest $request, BlotterRecord $blotter)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $validated = $request->validated();

        $blotter->reopen(BlotterStatus::Pending, Auth::user(), $validated['reason']);

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
