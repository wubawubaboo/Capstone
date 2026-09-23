<?php

namespace App\Http\Controllers;

use App\Actions\Blotter\FileBlotterCase;
use App\Enums\BlotterStatus;
use App\Enums\MediationStatus;
use App\Enums\ReportStatus;
use App\Exports\BlottersExport;
use App\Http\Requests\ExportFilterRequest;
use App\Http\Requests\Blotter\ReopenCaseRequest;
use App\Http\Requests\Blotter\ScheduleMediationRequest;
use App\Http\Requests\Blotter\StoreBlotterRequest;
use App\Http\Requests\Blotter\StoreVawcDetailRequest;
use App\Http\Requests\Blotter\UpdateMediationNotesRequest;
use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\User;
use App\Models\VawcDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BlotterController extends Controller
{
    public function index()
    {
        $blotters = BlotterRecord::forBarangay(Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->with(['report.user', 'receiver'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return Inertia::render('Secretary/BlotterManagement', [
            'blotters' => $blotters
        ]);
    }

    public function export(ExportFilterRequest $request)
    {
        $validated = $request->validated();

        $query = BlotterRecord::forBarangay(Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->with(['report.user', 'receiver']);

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereBetween('official_entry_date', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59',
            ]);
        }

        $blotters = $query->latest()->get();

        $filename = 'blotters-' . ($validated['start_date'] ?? now()->format('Y-m-d')) . '-to-' . ($validated['end_date'] ?? now()->format('Y-m-d')) . '.xlsx';

        return Excel::download(new BlottersExport($blotters), $filename);
    }

    public function create(Request $request)
    {
        $barangayId = Auth::user()->barangay_id;

        $pendingReports = Report::with('user')
            ->whereDoesntHave('blotter')
            ->whereIn('status', [ReportStatus::Pending, ReportStatus::InProgress])
            ->forBarangay($barangayId)
            ->get();

        return Inertia::render('Secretary/CreateBlotter', [
            'pendingReports' => $pendingReports,
            'selectedReportId' => $request->query('report_id', '')
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

    public function store(StoreBlotterRequest $request, FileBlotterCase $fileBlotterCase)
    {
        $barangayId = Auth::user()->barangay_id;

        $blotter = $fileBlotterCase($request->validated(), $barangayId, $request->user());

        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'Blotter', "Created Blotter Case #{$blotter->case_number}.");

        return to_route('secretary.blotters')->with('success', 'Blotter record created successfully.');
    }

    public function storeVawcDetail(StoreVawcDetailRequest $request, BlotterRecord $blotter)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $validated = $request->validated();

        VawcDetail::create([
            'blotter_record_id'    => $blotter->id,
            'officer_in_charge_id' => Auth::id(),
            'confidential_notes'   => $validated['confidential_notes'] ?? $blotter->incident_description,
        ]);

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'CREATE',
            'VAWC Blotter',
            "Flagged Case #{$blotter->case_number} as a confidential VAWC case."
        );

        return to_route('secretary.blotters')->with('success', 'Case flagged as a confidential VAWC case.');
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
                "Mediation session #{$schedule->meeting_number} scheduled."
            );

            return $schedule;
        });

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'CREATE',
            'Mediation Schedule',
            "Scheduled Session #{$schedule->meeting_number} for case #{$blotter->case_number}."
        );

        return back()->with('success', "Mediation session #{$schedule->meeting_number} scheduled successfully.");
    }

    public function caseHistory(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $blotter->load(['report.user', 'receiver', 'mediations', 'statusHistory.actor']);

        return Inertia::render('Secretary/CaseHistory', [
            'blotter' => $blotter
        ]);
    }

    public function downloadCaseReport(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $blotter->load(['barangay', 'report.user', 'receiver', 'mediations']);

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
            "Generated case report PDF for case #{$blotter->case_number}."
        );

        return $pdf->download("case-report-{$blotter->case_number}.pdf");
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
            'Mediation Schedule',
            "Updated notes for Session #{$mediation->meeting_number} of case #{$mediation->blotter->case_number}."
        );

        return back()->with('success', 'Mediation meeting notes saved successfully.');
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

    public function mediationMeetingDetails(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        $blotter->load(['report.user', 'receiver', 'mediations']);

        $latestMediation = $blotter->mediations->sortByDesc('scheduled_date')->first();
        $blotter->scheduled_date = $latestMediation ? $latestMediation->scheduled_date : null;

        return Inertia::render('Secretary/MediationMeetingDetails', [
            'blotter' => $blotter
        ]);
    }

    public function resolveCase(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        DB::transaction(function () use ($blotter) {
            $blotter->transitionStatus(BlotterStatus::Resolved, Auth::user(), 'Case resolved.');

            if ($blotter->report_id) {
                $report = Report::find($blotter->report_id);
                if ($report) {
                    $report->transitionStatus(ReportStatus::Completed, Auth::user(), 'Linked blotter case resolved.');
                }
            }
        });

        return redirect()->route('secretary.blotters')->with('success', 'Case resolved and report closed.');
    }

    public function escalateCase(BlotterRecord $blotter, Request $request)
    {
        abort_unless($request->user()->can('view', $blotter), 404);

        DB::transaction(function () use ($blotter) {
            $blotter->transitionStatus(BlotterStatus::EscalatedToCourt, Auth::user(), 'Case escalated to court.');

            if ($blotter->report_id) {
                $report = Report::find($blotter->report_id);
                if ($report) {
                    $report->transitionStatus(ReportStatus::Escalated, Auth::user(), 'Linked blotter case escalated to court.');
                }
            }
        });

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'UPDATE',
            'Blotter',
            "Escalated Case #{$blotter->case_number} to Court."
        );

        return redirect()->route('secretary.blotters')->with('success', 'Case escalated to court.');
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
            'Blotter',
            "Reopened Case #{$blotter->case_number}: {$validated['reason']}"
        );

        return back()->with('success', 'Case reopened successfully.');
    }
}
