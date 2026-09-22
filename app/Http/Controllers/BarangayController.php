<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Barangay\StoreBarangayRequest;
use App\Http\Requests\Barangay\UpdateBarangayRequest;
use App\Models\Barangay;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BarangayController extends Controller
{

    public function index()
    {
        return Inertia::render('Admin/BarangayManagement', [
            'barangays' => Barangay::latest()->get()
        ]);
    }

    public function store(StoreBarangayRequest $request)
    {
        $barangay = Barangay::create($request->validated());

        SystemLog::record('CREATE', 'Barangay', "Created barangay \"{$barangay->name}\".", $barangay->id);

        return redirect()->back()->with('message', 'Barangay created successfully.');
    }

    public function update(UpdateBarangayRequest $request, Barangay $barangay)
    {
        $barangay->update($request->validated());

        SystemLog::record('UPDATE', 'Barangay', "Updated barangay \"{$barangay->name}\".", $barangay->id);

        return redirect()->back()->with('message', 'Barangay updated successfully.');
    }

    public function destroy(Barangay $barangay)
    {
        // Prevent deletion if the barangay has active relationships
        if ($barangay->users()->exists() || $barangay->reports()->exists() || $barangay->blotters()->exists()) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot delete barangay because it contains active users or records.'
            ]);
        }

        SystemLog::record('DELETE', 'Barangay', "Deleted barangay \"{$barangay->name}\".", $barangay->id);

        $barangay->delete();

        return redirect()->back()->with('message', 'Barangay deleted successfully.');
    }

    // ========================================================
    // SECRETARY ACTIONS (Managing their assigned barangay)
    // ========================================================
    
    public function editProfile(Request $request)
    {
        return Inertia::render('Secretary/BarangayProfile', [
            'barangay' => $request->user()->barangay 
        ]);
    }

    public function updateProfile(UpdateBarangayRequest $request)
    {
        $barangay = $request->user()->barangay;

        $barangay->update($request->validated());

        SystemLog::record('UPDATE', 'Barangay', "Updated barangay profile for \"{$barangay->name}\".");

        return redirect()->back()->with('message', 'Barangay details updated successfully.');
    }
}