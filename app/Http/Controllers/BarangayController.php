<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:barangays',
            'contact_number' => 'nullable|string|max:255',
        ]);

        Barangay::create($validated);
        
        return redirect()->back()->with('message', 'Barangay created successfully.');
    }

    public function update(Request $request, Barangay $barangay)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:barangays,name,' . $barangay->id,
            'contact_number' => 'nullable|string|max:255',
        ]);

        $barangay->update($validated);
        
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

    public function updateProfile(Request $request)
    {
        $barangay = $request->user()->barangay;

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:barangays,name,' . $barangay->id,
            'contact_number' => 'nullable|string|max:255',
        ]);

        $barangay->update($validated);
        
        return redirect()->back()->with('message', 'Barangay details updated successfully.');
    }
}