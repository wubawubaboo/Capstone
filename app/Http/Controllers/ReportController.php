<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\User;
use App\Services\OpenStreetMapService;
use App\Services\PhilSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\SosTriggered;
use Inertia\Inertia;
use App\Jobs\SendEmergencySmsJob;

class ReportController extends Controller {
    
    public function store(Request $request) 
    {
        $validated = $request->validate([
            'incident_type' => 'required|string|max:255',
            'description' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'attachment' => 'nullable|image|max:2048',
        ]);

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
            'status' => 'Pending',
        ]);

        return to_route('resident.home')->with('success', 'Emergency report submitted successfully.');  
    }

    public function showAttachment(Report $report) 
    {
        if (Auth::id() !== $report->user_id && Auth::user()->role !== 'secretary') {
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
        return Inertia::render('Resident/Profile');
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

    public function storeEmergency(Request $request, PhilSmsService $smsService, OpenStreetMapService $geocodeService)
    {
        $validated = $request->validate([
            'emergency_type' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();
        $barangay = $user->barangay->name ?? 'San Nicolas';
        $locationData = $geocodeService->reverseGeocode($validated['latitude'], $validated['longitude']);
        $address = $locationData ? $locationData['full_address'] : 'Unknown Location (Check GPS Map)';
        
        $isOutsideJurisdiction = false;
        if ($locationData && isset($locationData['village'])) {
            if (stripos($locationData['village'], 'San Nicolas') === false) {
                $isOutsideJurisdiction = true;
            }
        }

        $report = Report::create([
            'user_id' => Auth::id(),
            'incident_type' => 'SOS_CRITICAL', 
            'description' => 'SOS EMERGENCY: ' . $validated['emergency_type'] . ' at ' . $address, 
            'latitude' => $validated['latitude'], 
            'longitude' => $validated['longitude'], 
            'status' => 'pending',
        ]);

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

    public function secretaryIndex()
    {
        $reports = Report::with('user')
            ->orderByRaw("CASE WHEN incident_type = 'SOS_CRITICAL' AND status != 'completed' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Secretary/Reports', [
            'reports' => $reports
        ]);
    }

    public function updateStatus(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $report->update(['status' => $validated['status']]);
        
        return back()->with('success', 'Report status updated successfully.');
    }
}