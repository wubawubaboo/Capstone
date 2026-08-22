<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\User;
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
    /** @var User $authUser */
    $authUser = Auth::user();

    // Fetch user with barangay relationship
    $user = User::with('barangay')->findOrFail($authUser->id);

    // Fetch mediation updates belonging to this resident (strictly non-VAWC)
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

    return Inertia::render('Resident/Profile', [
        'profileUser' => $user,
        'caseUpdates' => $caseUpdates,
    ]);
}
}