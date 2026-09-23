<?php

namespace App\Http\Controllers;

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
use App\Services\OpenStreetMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Events\SosTriggered;
use Inertia\Inertia;
use App\Jobs\SendEmergencySmsJob;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller {
    
    public function store(StoreReportRequest $request)
    {
        $validated = $request->validated();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reports');
        }

        Report::create([
            'user_id' => Auth::id(),
            'incident_type' => $validated['incident_type'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'attachment_path' => $attachmentPath,
            'status' => ReportStatus::Pending,
        ]);

        return to_route('resident.home')->with('success', 'Emergency report submitted successfully.');  
    }

    public function showAttachment(Report $report)
    {
        $isOwner = Auth::id() === $report->user_id;
        $isStaffForBarangay = in_array(Auth::user()->role, ['secretary', 'vawc'])
            && $report->user
            && $report->user->barangay_id === Auth::user()->barangay_id;

        if (!$isOwner && !$isStaffForBarangay) {
            abort(403, 'Unauthorized to view this evidence.');
        }

        $filePath = Storage::disk('local')->path($report->attachment_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->file($filePath);
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

        $reports = Report::where('user_id', $user->id)->latest()->get();
        $serviceRequests = \App\Models\ServiceRequest::where('requester_id', $user->id)->latest()->get();
        $documentRequests = \App\Models\DocumentRequest::with('documentType')->where('requester_id', $user->id)->latest()->get();

        return Inertia::render('Resident/Tracking', [
            'caseUpdates' => $caseUpdates,
            'reports' => $reports,
            'serviceRequests' => $serviceRequests,
            'documentRequests' => $documentRequests,
        ]);
    }

    public function storeEmergency(StoreEmergencyRequest $request, OpenStreetMapService $geocodeService)
    {
        $validated = $request->validated();

        $user = Auth::user();
        $barangay = $user->barangay->name ?? 'San Nicolas';
        $locationData = $geocodeService->reverseGeocode($validated['latitude'], $validated['longitude']);
        $address = $locationData ? $locationData['full_address'] : 'Unknown Location (Check GPS Map)';
        
        $withinBoundary = $user->barangay?->containsPoint(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if ($withinBoundary !== null) {
            // Barangay has a real boundary polygon on file: trust it.
            $isOutsideJurisdiction = !$withinBoundary;
        } else {
            // No polygon fetched yet for this barangay: fall back to the
            // coarser reverse-geocoded address match.
            $isOutsideJurisdiction = $locationData
                && isset($locationData['village'])
                && stripos($locationData['village'], $barangay) === false;
        }

        $report = Report::create([
            'user_id' => Auth::id(),
            'incident_type' => 'SOS_CRITICAL', 
            'description' => 'SOS EMERGENCY: ' . $validated['emergency_type'] . ' at ' . $address, 
            'latitude' => $validated['latitude'], 
            'longitude' => $validated['longitude'],
            'status' => ReportStatus::Pending,
        ]);

        SystemLog::record('CREATE', 'Report', "Triggered SOS emergency ({$validated['emergency_type']}) at {$address}.");

        broadcast(new SosTriggered($report));

        $message = "URGENT SOS - {$barangay}: {$validated['emergency_type']} reported at {$address}.";
        if ($isOutsideJurisdiction) {
            $message .= " (WARNING: Potentially outside barangay boundaries).";
        }
        
        $policeOfficers = User::where('role', 'barangay_police')
            ->where('barangay_id', $user->barangay_id)
            ->whereNotNull('phone_number')
            ->get();

        SendEmergencySmsJob::dispatch($policeOfficers, $message);

        return back()->with('success', 'Emergency SOS triggered successfully.');
    }

    public function secretaryIndex(Request $request)
    {
        $barangayId = Auth::user()->barangay_id;

        $query = Report::with('user')
            ->forBarangay($barangayId);

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

        $barangayId = Auth::user()->barangay_id;

        $query = Report::with('user')
            ->forBarangay($barangayId);

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
        $barangayId = Auth::user()->barangay_id;

        $query = Report::with('user')
            ->forBarangay($barangayId);

        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        $reports = $query->orderByRaw("CASE WHEN incident_type = 'SOS_CRITICAL' AND status != 'completed' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('VAWC/Reports', [
            'reports' => $reports,
            'filters' => $request->only('status')
        ]);
    }

    public function updateStatus(UpdateReportStatusRequest $request, Report $report)
    {
        abort_unless($report->user && $report->user->barangay_id === Auth::user()->barangay_id, 404);

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

        SystemLog::record('UPDATE', 'Report', "Updated Report #{$report->id} status to {$validated['status']}.", $report->user->barangay_id);

        return back()->with('success', 'Report status updated successfully.');
    }

    public function acknowledge(Report $report)
    {
        abort_unless($report->user && $report->user->barangay_id === Auth::user()->barangay_id, 404);

        if (!$report->acknowledged_at) {
            $report->update(['acknowledged_at' => now()]);

            SystemLog::record('UPDATE', 'Report', "Acknowledged SOS Report #{$report->id}.", $report->user->barangay_id);
        }

        return back()->with('success', 'Report acknowledged.');
    }
}