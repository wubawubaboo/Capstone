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

    /** Public/resident hotlines page: barangay numbers come from each barangay's contact_number. */
    public function hotlines(Request $request)
    {
        $userBarangayId = $request->user()?->barangay_id;

        $barangays = Barangay::whereNotNull('contact_number')
            ->where('contact_number', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'contact_number'])
            // Show the viewer's own barangay first when logged in.
            ->sortBy(fn ($b) => $b->id === $userBarangayId ? 0 : 1)
            ->values();

        return Inertia::render('Public/Hotlines', [
            'barangayHotlines' => $barangays->map(fn ($b) => [
                'name' => 'Brgy. ' . $b->name,
                'number' => $b->contact_number,
            ]),
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
        if ($barangay->hasDependents()) {
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