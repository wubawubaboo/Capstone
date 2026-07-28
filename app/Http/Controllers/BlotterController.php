<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlotterController extends Controller
{
    public function index()
    {
        $blotters = BlotterRecord::with(['report', 'receiver'])->latest()->paginate(15);
        return Inertia::render('blotters.index', compact('blotters'));
    }

    public function scheduleMediation(Request $request, BlotterRecord $blotter)
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date|after:today',
            'meeting_number' => 'required|integer|min:1'
        ]);

        $blotter->scheduleMediation($validated['scheduled_date'], $validated['meeting_number']);

        SystemLog::logAction($blotter->barangay_id, Auth::id(), 'SCHEDULE', 'Mediation', "Scheduled meeting #{$validated['meeting_number']} for Case #{$blotter->case_number}.");

        return back()->with('success', 'Mediation scheduled successfully.');
    }

    public function storeVawcDetail(Request $request, BlotterRecord $blotter)
    {
        $validated = $request->validate([
            'confidential_notes' => 'required|string'
        ]);

        $blotter->vawcDetail()->create([
            'officer_in_charge_id' => Auth::id(),
            'confidential_notes' => $validated['confidential_notes']
        ]);

        return back()->with('success', 'VAWC details attached securely.');
    }
}