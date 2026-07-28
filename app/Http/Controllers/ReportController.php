<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        // City admins see all, Secretaries see only their barangay's reports
        $reports = Report::with(['reporter', 'incidentBarangay'])->latest()->paginate(15);
        return Inertia::render('reports.index', compact('reports'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'incident_barangay_id' => 'required|exists:barangays,id',
            'type' => 'required|string|max:255',
            'gps_coordinates' => 'nullable|string',
            'is_outside_jurisdiction' => 'boolean'
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $report = $user->reports()->create($validated + ['status' => 'Pending']);

        SystemLog::logAction($report->incident_barangay_id, $user->id, 'CREATE', 'Reports', "Submitted a new incident report.");

        return back()->with('success', 'Report submitted successfully.');
    }

    public function escalate(Request $request, Report $report)
    {
        $request->validate(['case_number' => 'required|string|unique:blotter_records,case_number']);

        $blotter = $report->escalateToBlotter($request->case_number, true);

        SystemLog::logAction($report->incident_barangay_id, Auth::id(), 'ESCALATE', 'Reports', "Escalated Report #{$report->id} to Blotter Case #{$blotter->case_number}.");

        return redirect()->route('blotters.show', $blotter)->with('success', 'Report escalated to Blotter.');
    }
}