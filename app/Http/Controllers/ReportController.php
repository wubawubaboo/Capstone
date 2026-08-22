<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MediationSchedule;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
    public function showAttachment(Report $report) {
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
    $userId = Auth::id();
    $barangayId = Auth::user()->barangay_id;

    // Securely retrieve mediation notices belonging strictly to the logged-in resident
    $caseUpdates = MediationSchedule::whereHas('blotter', function ($query) use ($userId, $barangayId) {
            $query->where('barangay_id', $barangayId)
                  ->whereDoesntHave('vawcDetail') // Enforce R.A. 9262 confidentiality
                  ->where(function ($sub) use ($userId) {
                      $sub->where('complainant_id', $userId)
                          ->orWhere('receiver_id', $userId)
                          ->orWhereHas('report', function ($r) use ($userId) {
                              $r->where('user_id', $userId);
                          });
                  });
        })
        ->with(['blotter' => function ($query) {
            // Minimize exposed attributes
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

    return Inertia::render('Resident/Profile', [
        'caseUpdates' => $caseUpdates,
    ]);
    }
}