<?php

namespace App\Http\Controllers;

use App\Enums\BlotterStatus;
use App\Exports\BlottersExport;
use App\Http\Requests\Blotter\ReopenCaseRequest;
use App\Http\Requests\Blotter\ScheduleMediationRequest;
use App\Http\Requests\Blotter\StoreBlotterRequest;
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
    $blotters = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
        ->whereDoesntHave('vawcDetail')
        ->with(['report.user', 'receiver'])
        ->latest()
        ->paginate(self::PER_PAGE);

    return Inertia::render('Secretary/BlotterManagement', [
        'blotters' => $blotters
    ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
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
            ->whereIn('status', ['Pending', 'pending', 'in_progress'])
            ->whereHas('user', fn ($q) => $q->where('barangay_id', $barangayId))
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

    public function store(StoreBlotterRequest $request)
    {
        $validated = $request->validated();

        $barangayId = Auth::user()->barangay_id;

        $complainantId = null;
        $complainantName = null;
        $incidentType = null;
        $incidentDescription = null;
        $reportId = null;

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
            $complainantId = $validated['complainant_id'];
            $user = User::where('barangay_id', $barangayId)->find($complainantId);
            abort_unless($user, 422, 'Complainant must be a resident of your barangay.');

            $complainantName = $user->full_name;
            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        if ($validated['is_registered_respondent']) {
            $respondentExists = User::where('barangay_id', $barangayId)->where('id', $validated['receiver_id'])->exists();
            abort_unless($respondentExists, 422, 'Respondent must be a resident of your barangay.');
        }

        $blotter = DB::transaction(function () use ($barangayId, $reportId, $complainantId, $complainantName, $incidentType, $incidentDescription, $validated) {
            $caseNumber = BlotterRecord::generateCaseNumber('BLT', $barangayId);

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
                'note'        => 'Case filed.',
            ]);

            return $blotter;
        });

        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'Blotter', "Created Blotter Case #{$blotter->case_number}.");

        return to_route('secretary.blotters')->with('success', 'Blotter record created successfully.');
    }

    public function storeVawcDetail(Request $request, $blotter)
    {
        $blotterRecord = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->findOrFail($blotter);

        $validated = $request->validate([
            'confidential_notes' => 'nullable|string',
        ]);

        VawcDetail::create([
            'blotter_record_id'    => $blotterRecord->id,
            'officer_in_charge_id' => Auth::id(),
            'confidential_notes'   => $validated['confidential_notes'] ?? $blotterRecord->incident_description,
        ]);

        SystemLog::logAction(
            Auth::user()->barangay_id,
            Auth::id(),
            'CREATE',
            'VAWC Blotter',
            "Flagged Case #{$blotterRecord->case_number} as a confidential VAWC case."
        );

        return to_route('secretary.blotters')->with('success', 'Case flagged as a confidential VAWC case.');
    }

    public function scheduleMediation(ScheduleMediationRequest $request, $id)
    {
    $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
        ->whereDoesntHave('vawcDetail')
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
                "Mediation session #{$schedule->meeting_number} scheduled."
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
        'Mediation Schedule',
        "Scheduled Session #{$schedule->meeting_number} for case #{$blotter->case_number}."
    );

    return back()->with('success', "Mediation session #{$schedule->meeting_number} scheduled successfully.");
    }


    public function caseHistory($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->with(['report.user', 'receiver', 'mediations', 'statusHistory.actor'])
            ->findOrFail($id);

        return Inertia::render('Secretary/CaseHistory', [
            'blotter' => $blotter
        ]);
    }

    public function downloadCaseReport($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->with(['barangay', 'report.user', 'receiver', 'mediations'])
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
            "Generated case report PDF for case #{$blotter->case_number}."
        );

        return $pdf->download("case-report-{$blotter->case_number}.pdf");
    }

    public function updateMediationNotes(UpdateMediationNotesRequest $request, $id)
    {
        $mediation = MediationSchedule::whereHas('blotter', function ($query) {
                $query->where('barangay_id', Auth::user()->barangay_id)
                    ->whereDoesntHave('vawcDetail');
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

    public function mediationMeetingDetails($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->with(['report.user', 'receiver', 'mediations'])
            ->findOrFail($id);

        $latestMediation = $blotter->mediations->sortByDesc('scheduled_date')->first();
        $blotter->scheduled_date = $latestMediation ? $latestMediation->scheduled_date : null;

        return Inertia::render('Secretary/MediationMeetingDetails', [
            'blotter' => $blotter
        ]);
    }

    public function resolveCase($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->findOrFail($id);

        try {
            DB::transaction(function () use ($blotter) {
                $blotter->transitionStatus(BlotterStatus::Resolved, Auth::user(), 'Case resolved.');

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

        return redirect()->route('secretary.blotters')->with('success', 'Case resolved and report closed.');
    }

    public function escalateCase($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
            ->findOrFail($id);

        try {
            DB::transaction(function () use ($blotter) {
                $blotter->transitionStatus(BlotterStatus::EscalatedToCourt, Auth::user(), 'Case escalated to court.');

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
            'Blotter',
            "Escalated Case #{$blotter->case_number} to Court."
        );

        return redirect()->route('secretary.blotters')->with('success', 'Case escalated to court.');
    }

    public function reopenCase(ReopenCaseRequest $request, $id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereDoesntHave('vawcDetail')
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
            'Blotter',
            "Reopened Case #{$blotter->case_number}: {$validated['reason']}"
        );

        return back()->with('success', 'Case reopened successfully.');
    }
}