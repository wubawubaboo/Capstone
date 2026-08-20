<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\VawcDetail;
use App\Models\Report;
use App\Models\User;
use App\Models\MediationSchedule;
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

    public function analytics()
    {
    $barangayId = Auth::user()->barangay_id;

    $totalVawcCases = BlotterRecord::where('barangay_id', $barangayId)
        ->whereHas('vawcDetail')
        ->count();

    $activeBpoCount = VawcDetail::whereHas('blotter', function ($query) use ($barangayId) {
            $query->where('barangay_id', $barangayId);
        })
        ->where('has_bpo', true)
        ->count();

    $violationDistribution = VawcDetail::whereHas('blotter', function ($query) use ($barangayId) {
            $query->where('barangay_id', $barangayId);
        })
        ->select('violation_type as name', DB::raw('count(*) as value'))
        ->groupBy('violation_type')
        ->get();

    $caseStatusData = BlotterRecord::where('barangay_id', $barangayId)
        ->whereHas('vawcDetail')
        ->select('status as name', DB::raw('count(*) as value'))
        ->groupBy('status')
        ->get();

    return Inertia::render('VAWC/Analytics', [
        'totalVawcCases' => $totalVawcCases,
        'activeBpoCount' => $activeBpoCount,
        'violationDistribution' => $violationDistribution,
        'caseStatusData' => $caseStatusData,
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

    public function create()
    {
        $residents = User::where('barangay_id', Auth::user()->barangay_id)
            ->where('role', 'resident')
            ->select('id', 'full_name', 'email')
            ->get();

        return Inertia::render('VAWC/CreateBlotter', [
            'residents' => $residents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'is_registered_complainant' => 'required|boolean',
            'complainant_id' => 'nullable|required_if:is_registered_complainant,true|exists:users,id',
            'complainant_name' => 'nullable|required_if:is_registered_complainant,false|string|max:255',
            'is_registered_respondent' => 'required|boolean',
            'receiver_id' => 'nullable|required_if:is_registered_respondent,true|exists:users,id',
            'receiver_name' => 'nullable|required_if:is_registered_respondent,false|string|max:255',
            'incident_type' => 'required|string|max:255',
            'description' => 'required|string',
            'relationship_to_victim' => 'required|string|max:255',
            'violation_type' => 'required|string|max:255',
            'has_bpo' => 'required|boolean',
        ]);

        $barangayId = Auth::user()->barangay_id;

        $report = Report::create([
            'barangay_id' => $barangayId,
            'user_id' => $validated['is_registered_complainant'] ? $validated['complainant_id'] : Auth::id(),
            'incident_type' => $validated['incident_type'],
            'description' => $validated['description'],
            'status' => 'Blottered',
        ]);

        $blotter = BlotterRecord::create([
            'barangay_id' => $barangayId,
            'report_id' => $report->id,
            'receiver_id' => $validated['is_registered_respondent'] ? $validated['receiver_id'] : null,
            'receiver_name' => !$validated['is_registered_respondent'] ? $validated['receiver_name'] : null,
            'status' => 'Pending',
        ]);

        VawcDetail::create([
            'blotter_id' => $blotter->id,
            'officer_in_charge_id' => Auth::id(),
            'relationship_to_victim' => $validated['relationship_to_victim'],
            'violation_type' => $validated['violation_type'],
            'has_bpo' => $validated['has_bpo'],
        ]);

        return redirect()->route('vawc.blotters')->with('success', 'VAWC blotter record registered successfully.');
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