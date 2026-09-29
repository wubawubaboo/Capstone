<?php

namespace App\Http\Controllers;

use App\Actions\Blotter\FileBlotterCase;
use App\Enums\CaseDesk;
use App\Exports\BlottersExport;
use App\Http\Requests\Blotter\StoreBlotterRequest;
use App\Http\Requests\ExportFilterRequest;
use App\Models\BlotterRecord;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The secretary desk's blotter cases (everything except VAWC cases). The
 * case workflow itself is shared with the VAWC desk in CaseDeskController.
 */
class BlotterController extends CaseDeskController
{
    protected function desk(): CaseDesk
    {
        return CaseDesk::Secretary;
    }

    public function index()
    {
        $blotters = $this->desk()->scopeCases(BlotterRecord::forBarangay(Auth::user()->barangay_id))
            ->with(['report.user', 'receiver'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return Inertia::render('Secretary/BlotterManagement', [
            'blotters' => $blotters
        ]);
    }

    public function export(ExportFilterRequest $request)
    {
        $validated = $request->validated();

        $query = $this->desk()->scopeCases(BlotterRecord::forBarangay(Auth::user()->barangay_id))
            ->with(['report.user', 'receiver']);

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereBetween('official_entry_date', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59',
            ]);
        }

        $blotters = $query->latest()->get();

        $filename = 'blotters-' . ($validated['start_date'] ?? now()->format('Y-m-d')) . '-to-' . ($validated['end_date'] ?? now()->format('Y-m-d')) . '.xlsx';

        return Excel::download(new BlottersExport($blotters), $filename);
    }

    public function store(StoreBlotterRequest $request, FileBlotterCase $fileBlotterCase)
    {
        $barangayId = Auth::user()->barangay_id;

        DB::transaction(function () use ($request, $fileBlotterCase, $barangayId) {
            $blotter = $fileBlotterCase($request->validated(), $barangayId, $request->user());

            SystemLog::logAction($barangayId, Auth::id(), 'CREATE', 'Blotter', "Created Blotter Case #{$blotter->case_number}.");
        });

        return to_route('secretary.blotters')->with('success', 'Blotter record created successfully.');
    }
}
