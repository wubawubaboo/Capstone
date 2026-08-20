<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\VawcDetail;
use App\Models\Report;
use App\Models\User;
use App\Models\MediationSchedule;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class VawcController extends Controller
{
    public function index()
    {
        $vawcBlotters = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->with(['report.user', 'receiver', 'vawcDetail'])
            ->latest()
            ->paginate(10);

        return Inertia::render('VAWC/BlotterManagement', [
            'blotters' => $vawcBlotters,
        ]);
    }

    public function create()
    {
        $residents = User::where('barangay_id', Auth::user()->barangay_id)
            ->where('role', 'resident')
            ->select('id', 'full_name', 'phone_number')
            ->get();

        return Inertia::render('VAWC/CreateBlotter', [
            'residents' => $residents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'is_registered_complainant' => 'required|boolean',
            'complainant_id'            => 'nullable|required_if:is_registered_complainant,true,1|exists:users,id',
            'complainant_name'          => 'nullable|required_if:is_registered_complainant,false,0|string|max:255',
            
            'is_registered_respondent'  => 'required|boolean',
            'receiver_id'               => 'nullable|required_if:is_registered_respondent,true,1|exists:users,id',
            'receiver_name'             => 'nullable|required_if:is_registered_respondent,false,0|string|max:255',
            
            'incident_type'             => 'required|string|max:255',
            'description'               => 'required|string',
            'confidential_notes'        => 'nullable|string',
        ]);

        $barangayId = Auth::user()->barangay_id;

        // Determine complainant details
        $complainantId = $validated['is_registered_complainant'] ? $validated['complainant_id'] : null;
        $complainantName = null;

        if ($complainantId) {
            $user = User::find($complainantId);
            $complainantName = $user ? $user->full_name : null;
        } else {
            $complainantName = $validated['complainant_name'];
        }

        $caseNumber = 'VAWC-' . date('Y') . '-' . str_pad(BlotterRecord::where('barangay_id', $barangayId)->count() + 1, 4, '0', STR_PAD_LEFT);

        $blotter = BlotterRecord::create([
            'barangay_id'          => $barangayId,
            'report_id'            => null,
            'complainant_id'       => $complainantId,
            'complainant_name'     => $complainantName,
            'incident_type'        => $validated['incident_type'],
            'incident_description' => $validated['description'],
            'receiver_id'          => $validated['is_registered_respondent'] ? $validated['receiver_id'] : null,
            'receiver_name'        => !$validated['is_registered_respondent'] ? $validated['receiver_name'] : null,
            'case_number'          => $caseNumber,
            'status'               => 'Pending',
            'official_entry_date'  => now(),
        ]);

        // Create linked confidential VAWC details
        VawcDetail::create([
            'blotter_record_id'    => $blotter->id,
            'officer_in_charge_id' => Auth::id(),
            'confidential_notes'   => !empty($validated['confidential_notes']) ? $validated['confidential_notes'] : $validated['description'],
        ]);

        // Audit Trail
        SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'VAWC Blotter', "Recorded confidential VAWC case #{$caseNumber}.");

        return redirect()->route('vawc.blotters')->with('success', 'VAWC incident record created successfully.');
    }

    public function analytics()
    {
        $barangayId = Auth::user()->barangay_id;

        $totalVawcCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->count();

        $settledCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->where('status', 'Settled')
            ->count();

        $escalatedCases = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->where('status', 'Escalated')
            ->count();

        $incidentDistribution = BlotterRecord::where('barangay_id', $barangayId)
            ->whereHas('vawcDetail')
            ->select('incident_type as name', DB::raw('count(*) as value'))
            ->groupBy('incident_type')
            ->get();

        $caseStatusData = BlotterRecord::where('barangay_id', $barangayId)
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

    public function caseHistory($id)
    {
        $blotter = BlotterRecord::where('barangay_id', Auth::user()->barangay_id)
            ->whereHas('vawcDetail')
            ->with(['report.user', 'receiver', 'vawcDetail', 'mediations'])
            ->findOrFail($id);

        return Inertia::render('VAWC/CaseHistory', [
            'blotter' => $blotter,
        ]);
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
}