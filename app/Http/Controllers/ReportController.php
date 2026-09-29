<?php

namespace App\Http\Controllers;

use App\Actions\Report\TriggerSos;
use App\Enums\ReportStatus;
use App\Exports\ReportsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportFilterRequest;
use App\Http\Requests\Report\StoreEmergencyRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportStatusRequest;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\SecureFileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller {
    
    public function store(StoreReportRequest $request, SecureFileStore $secureFiles)
    {
        $validated = $request->validated();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $secureFiles->store($request->file('attachment'), 'reports');
        }

        DB::transaction(function () use ($validated, $request, $attachmentPath) {
            $report = Report::create([
                'user_id' => Auth::id(),
                'incident_type' => $validated['incident_type'],
                'description' => $validated['description'],
                'is_vawc' => $request->boolean('is_vawc'),
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => ReportStatus::Pending,
            ]);

            // The audit trail is visible to the citywide admin, so a VAWC
            // report is recorded without its incident type.
            SystemLog::record('CREATE', 'Report', $report->is_vawc
                ? "Filed confidential VAWC report #{$report->id}."
                : "Filed incident report #{$report->id} ({$report->incident_type}).");
        });

        $message = $request->boolean('is_vawc')
            ? 'Your report was sent confidentially to the barangay VAWC desk.'
            : 'Emergency report submitted successfully.';

        return to_route('resident.home')->with('success', $message);
    }

    public function showAttachment(Report $report, SecureFileStore $secureFiles)
    {
        abort_unless(Auth::user()->can('view', $report), 403, 'Unauthorized to view this evidence.');
        abort_unless($secureFiles->exists($report->attachment_path), 404, 'File not found.');

        return $secureFiles->response($report->attachment_path);
    }

    public function profile()
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $user = User::with('barangay')->findOrFail($authUser->id);

        return Inertia::render('Resident/Profile', [
            'profileUser' => $user,
        ]);
    }


    public function tracking()
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $user = User::with('barangay')->findOrFail($authUser->id);

        $caseUpdates = MediationSchedule::whereHas('blotter', function ($query) use ($user) {
                $query->where('barangay_id', $user->barangay_id)
                      ->whereDoesntHave('vawcDetail')
                      ->where(function ($sub) use ($user) {
                          $sub->where('complainant_id', $user->id)
                              ->orWhere('receiver_id', $user->id)
                              ->orWhereHas('report', function ($r) use ($user) {
                                  $r->where('user_id', $user->id);
                              });
                      });
            })
            ->with(['blotter' => function ($query) {
                $query->select('id', 'case_number', 'incident_type', 'status');
            }])
            ->orderBy('scheduled_date', 'desc')
            ->take(10)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id'             => $schedule->id,
                    'case_number'    => $schedule->blotter->case_number ?? 'N/A',
                    'incident_type'  => $schedule->blotter->incident_type ?? 'General Incident',
                    'meeting_number' => $schedule->meeting_number,
                    'status'         => $schedule->status,
                    'scheduled_date' => $schedule->scheduled_date,
                ];
            });

        // Each tab pages independently, so moving through one keeps the others' pages.
        $reports = Report::where('user_id', $user->id)->latest()
            ->paginate(self::PER_PAGE, ['*'], 'reports_page')->withQueryString();
        $serviceRequests = \App\Models\ServiceRequest::where('requester_id', $user->id)->latest()
            ->paginate(self::PER_PAGE, ['*'], 'services_page')->withQueryString();
        $documentRequests = \App\Models\DocumentRequest::with('documentType')->where('requester_id', $user->id)->latest()
            ->paginate(self::PER_PAGE, ['*'], 'documents_page')->withQueryString();

        return Inertia::render('Resident/Tracking', [
            'caseUpdates' => $caseUpdates,
            'reports' => $reports,
            'serviceRequests' => $serviceRequests,
            'documentRequests' => $documentRequests,
        ]);
    }

    public function storeEmergency(StoreEmergencyRequest $request, TriggerSos $triggerSos)
    {
        $validated = $request->validated();

        $triggerSos($request->user(), $validated['emergency_type'], (float) $validated['latitude'], (float) $validated['longitude']);

        return back()->with('success', 'Emergency SOS triggered successfully.');
    }

    /**
     * Report picker on the Create Blotter pages: up to 10 of the signed-in
     * desk's open reports without a case, matched by report number, incident
     * type or reporter name. Descriptions are encrypted, so aren't searched.
     */
    public function searchPending(Request $request)
    {
        $term = trim(ltrim(trim((string) $request->query('q', '')), '#'));

        if ($term === '') {
            return response()->json([]);
        }

        $reports = Report::visibleTo(Auth::user())
            ->awaitingBlotter()
            ->where(function ($q) use ($term) {
                $q->where('incident_type', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$term}%"));

                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            })
            ->with('user:id,full_name')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($reports);
    }

    public function secretaryIndex(Request $request)
    {
        $query = Report::with('user')
            ->visibleTo(Auth::user());

        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        $reports = $query->orderByRaw("CASE WHEN incident_type = 'SOS_CRITICAL' AND status != 'completed' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Secretary/Reports', [
            'reports' => $reports,
            'filters' => $request->only('status')
        ]);
    }

    public function exportSecretary(ExportFilterRequest $request)
    {
        $validated = $request->validated();

        $query = Report::with('user')
            ->visibleTo(Auth::user());

        $query->when($validated['status'] ?? null, function ($q, $status) {
            return $q->where('status', $status);
        });

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereBetween('created_at', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59',
            ]);
        }

        $reports = $query->orderByRaw("CASE WHEN incident_type = 'SOS_CRITICAL' AND status != 'completed' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'reports-' . ($validated['start_date'] ?? now()->format('Y-m-d')) . '-to-' . ($validated['end_date'] ?? now()->format('Y-m-d')) . '.xlsx';

        return Excel::download(new ReportsExport($reports), $filename);
    }

    public function vawcIndex(Request $request)
    {
        $query = Report::with('user')
            ->visibleTo(Auth::user());

        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        // SOS alerts never reach the VAWC desk, so no SOS-first ordering here.
        $reports = $query->orderBy('created_at', 'desc')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('VAWC/Reports', [
            'reports' => $reports,
            'filters' => $request->only('status')
        ]);
    }

    public function updateStatus(UpdateReportStatusRequest $request, Report $report)
    {
        abort_unless(Auth::user()->can('update', $report), 404);

        $validated = $request->validated();

        $to = ReportStatus::from($validated['status']);
        $now = now();
        $lifecycleUpdates = [];

        // Backfill any skipped earlier stage so the timeline has no gaps.
        if ($to === ReportStatus::InProgress || $to === ReportStatus::Completed) {
            if (!$report->acknowledged_at) {
                $lifecycleUpdates['acknowledged_at'] = $now;
            }
        }

        if ($to === ReportStatus::InProgress && !$report->responded_at) {
            $lifecycleUpdates['responded_at'] = $now;
        }

        if ($to === ReportStatus::Completed) {
            if (!$report->responded_at) {
                $lifecycleUpdates['responded_at'] = $now;
            }
            if (!$report->resolved_at) {
                $lifecycleUpdates['resolved_at'] = $now;
            }
        }

        DB::transaction(function () use ($report, $to, $lifecycleUpdates) {
            $report->transitionStatus($to, Auth::user());

            if (!empty($lifecycleUpdates)) {
                $report->update($lifecycleUpdates);
            }
        });

        SystemLog::record('UPDATE', 'Report', "Updated Report #{$report->id} status to {$validated['status']}.", $report->barangay_id);

        return back()->with('success', 'Report status updated successfully.');
    }

    public function acknowledge(Report $report)
    {
        abort_unless(Auth::user()->can('update', $report), 404);

        if (!$report->acknowledged_at) {
            $report->update(['acknowledged_at' => now()]);

            SystemLog::record('UPDATE', 'Report', "Acknowledged SOS Report #{$report->id}.", $report->barangay_id);
        }

        return back()->with('success', 'Report acknowledged.');
    }
}