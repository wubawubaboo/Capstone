<?php

namespace App\Http\Controllers;

use App\Actions\Vawc\FileVawcCase;
use App\Enums\CaseDesk;
use App\Http\Requests\Vawc\StoreVawcBlotterRequest;
use App\Models\BlotterRecord;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * The VAWC desk's confidential cases. The case workflow itself is shared
 * with the secretary desk in CaseDeskController.
 */
class VawcController extends CaseDeskController
{
    protected function desk(): CaseDesk
    {
        return CaseDesk::Vawc;
    }

    public function index()
    {
        $vawcBlotters = $this->desk()->scopeCases(BlotterRecord::forBarangay(Auth::user()->barangay_id))
            ->with(['report.user', 'receiver', 'vawcDetail'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return Inertia::render('VAWC/BlotterManagement', [
            'blotters' => $vawcBlotters,
        ]);
    }

    public function store(StoreVawcBlotterRequest $request, FileVawcCase $fileVawcCase)
    {
        $barangayId = Auth::user()->barangay_id;

        DB::transaction(function () use ($request, $fileVawcCase, $barangayId) {
            $blotter = $fileVawcCase($request->validated(), $barangayId, $request->user());

            SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'VAWC Blotter', "Recorded confidential VAWC case #{$blotter->case_number}.");
        });

        return redirect()->route('vawc.blotters')->with('success', 'VAWC incident record created successfully.');
    }

    public function analytics()
    {
        $cases = fn () => $this->desk()->scopeCases(BlotterRecord::forBarangay(Auth::user()->barangay_id));

        return Inertia::render('VAWC/Analytics', [
            'totalVawcCases'       => $cases()->count(),
            'settledCases'         => $cases()->resolved()->count(),
            'escalatedCases'       => $cases()->escalatedToCourt()->count(),
            'incidentDistribution' => $cases()->select('incident_type as name', DB::raw('count(*) as value'))->groupBy('incident_type')->get(),
            'caseStatusData'       => $cases()->select('status as name', DB::raw('count(*) as value'))->groupBy('status')->get(),
        ]);
    }
}
