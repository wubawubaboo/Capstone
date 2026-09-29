<?php

namespace App\Http\Controllers;

use App\Enums\BlotterStatus;
use App\Enums\CaseDesk;
use App\Enums\MediationStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Http\Requests\Blotter\ReopenCaseRequest;
use App\Http\Requests\Blotter\ScheduleMediationRequest;
use App\Http\Requests\Blotter\UpdateMediationNotesRequest;
use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

abstract class CaseDeskController extends Controller
{
    abstract protected function desk(): CaseDesk;

    public function create(Request $request)
    {
        // Opened from a report's "file blotter" link: preselect that report.
        // Other reports are found through ReportController::searchPending.
        $selectedReport = $request->query('report_id')
            ? Report::with('user:id,full_name')->visibleTo(Auth::user())->awaitingBlotter()->find($request->query('report_id'))
            : null;

        return Inertia::render($this->page('CreateBlotter'), [
            'selectedReport' => $selectedReport,
        ]);
    }

    public function searchResidents(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $residents = User::where('barangay_id', Auth::user()->barangay_id)
            ->where('role', Role::Resident)
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                    ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'phone_number', 'address']);

        return response()->json($residents);
    }

    public function caseHistory(BlotterRecord $blotter, Request $request)
    {
        $this->authorizeCase($request, $blotter);

        $blotter->load(['report.user', 'receiver', 'mediations', 'statusHistory.actor', ...$this->desk()->extraCaseRelations()]);

        if ($this->desk()->isConfidential()) {
            $this->log('VIEW', $this->desk()->logModule(), "Viewed confidential VAWC case #{$blotter->case_number}.");
        }

        return Inertia::render($this->page('CaseHistory'), [
            'blotter' => $blotter,
        ]);
    }

    public function downloadCaseReport(BlotterRecord $blotter, Request $request)
    {
        $this->authorizeCase($request, $blotter);

        $blotter->load(['barangay', 'report.user', 'receiver', 'mediations', ...$this->desk()->extraCaseRelations()]);

        $pdf = Pdf::loadView('pdf.case-report', [
            'blotter'     => $blotter,
            'generatedBy' => Auth::user()->full_name,
            'generatedAt' => now(),
        ]);

        $confidential = $this->desk()->isConfidential() ? 'confidential VAWC ' : '';
        $this->log('EXPORT', $this->desk()->logModule(), "Generated {$confidential}case report PDF for case #{$blotter->case_number}.");

        return $pdf->download("{$this->desk()->reportFilePrefix()}-{$blotter->case_number}.pdf");
    }

    public function scheduleMediation(ScheduleMediationRequest $request, BlotterRecord $blotter)
    {
        $this->authorizeCase($request, $blotter);

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

            $session = $this->desk()->isConfidential() ? 'Confidential mediation session' : 'Mediation session';
            $blotter->transitionStatus(BlotterStatus::UnderMediation, Auth::user(), "{$session} #{$schedule->meeting_number} scheduled.");

            $this->log('CREATE', $this->desk()->mediationLogModule(), "Scheduled Session #{$schedule->meeting_number} for case #{$blotter->case_number}.");

            return $schedule;
        });

        return back()->with('success', "Mediation session #{$schedule->meeting_number} scheduled successfully.");
    }

    public function updateMediationNotes(UpdateMediationNotesRequest $request, MediationSchedule $mediation)
    {
        $this->authorizeCase($request, $mediation->blotter);

        $validated = $request->validated();

        DB::transaction(function () use ($mediation, $validated) {
            $mediation->update(['notes' => $validated['notes'] ?? null]);

            $this->log('UPDATE', $this->desk()->mediationLogModule(), "Updated notes for Session #{$mediation->meeting_number} of case #{$mediation->blotter->case_number}.");
        });

        return back()->with('success', 'Mediation meeting notes saved successfully.');
    }

    public function mediationCalendar(Request $request)
    {
        $month = $this->calendarMonth($request);

        $hearings = fn () => MediationSchedule::whereHas('blotter', function ($query) {
                $this->desk()->scopeCases($query->where('barangay_id', Auth::user()->barangay_id));
            })
            ->with(['blotter.report.user', 'blotter.receiver'])
            ->orderBy('scheduled_date', 'asc');

        return Inertia::render($this->page('MediationCalendar'), [
            'month' => $month->format('Y-m'),
            'schedules' => $hearings()->whereBetween('scheduled_date', $this->calendarRange($month))->get(),
            // The sidebar: the next hearings from now, whichever month is shown.
            'upcoming' => $hearings()->where('scheduled_date', '>=', now())->limit(10)->get(),
        ]);
    }

    public function resolveCase(BlotterRecord $blotter, Request $request)
    {
        $this->closeCase($request, $blotter, BlotterStatus::Resolved, ReportStatus::Completed, 'resolved', 'Resolved');

        return to_route("{$this->desk()->routePrefix()}.blotters")->with('success', 'Case resolved and report closed.');
    }

    public function escalateCase(BlotterRecord $blotter, Request $request)
    {
        $this->closeCase($request, $blotter, BlotterStatus::EscalatedToCourt, ReportStatus::Escalated, 'escalated to court', 'Escalated');

        return to_route("{$this->desk()->routePrefix()}.blotters")->with('success', 'Case escalated to court.');
    }

    public function reopenCase(ReopenCaseRequest $request, BlotterRecord $blotter)
    {
        $this->authorizeCase($request, $blotter);

        $validated = $request->validated();

        DB::transaction(function () use ($blotter, $validated) {
            $blotter->reopen(BlotterStatus::Pending, Auth::user(), $validated['reason']);

            $this->log('UPDATE', $this->desk()->logModule(), "Reopened {$this->desk()->caseLabel()} #{$blotter->case_number}: {$validated['reason']}");
        });

        return back()->with('success', 'Case reopened successfully.');
    }

    /** Closes a case and the report it was filed from, if any, with one audit entry. */
    private function closeCase(Request $request, BlotterRecord $blotter, BlotterStatus $caseStatus, ReportStatus $reportStatus, string $outcome, string $verb): void
    {
        $this->authorizeCase($request, $blotter);

        DB::transaction(function () use ($blotter, $caseStatus, $reportStatus, $outcome, $verb) {
            $blotter->transitionStatus($caseStatus, Auth::user(), "{$this->desk()->historyNoun()} {$outcome}.");

            $linked = $this->desk()->isConfidential() ? 'VAWC case' : 'blotter case';
            Report::find($blotter->report_id)?->transitionStatus($reportStatus, Auth::user(), "Linked {$linked} {$outcome}.");

            $toCourt = $caseStatus === BlotterStatus::EscalatedToCourt ? ' to Court' : '';
            $this->log('UPDATE', $this->desk()->logModule(), "{$verb} {$this->desk()->caseLabel()} #{$blotter->case_number}{$toCourt}.");
        });
    }

    /** 404 rather than 403, so a desk can't probe which case numbers exist on the other desk. */
    protected function authorizeCase(Request $request, BlotterRecord $blotter): void
    {
        abort_unless($request->user()->can('view', $blotter), 404);
    }

    protected function page(string $name): string
    {
        return "{$this->desk()->pageFolder()}/{$name}";
    }

    protected function log(string $action, string $module, string $description): void
    {
        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), $action, $module, $description);
    }
}
