<?php

namespace App\Http\Controllers;

use App\Models\BarangayAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index()
    {
        $assets = BarangayAsset::where('barangay_id', Auth::user()->barangay_id)
            ->where('is_archived', false)
            ->latest()
            ->paginate(10);

        return Inertia::render('Secretary/AssetManagement', [
            'assets' => $assets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_type' => 'required|string|max:255',
        ]);

        BarangayAsset::create([
            'barangay_id' => Auth::user()->barangay_id,
            'asset_name' => $validated['asset_name'],
            'asset_type' => $validated['asset_type'],
            'is_available' => true, 
            'is_archived' => false,
        ]);

        return back()->with('success', 'New asset added successfully.');
    }

    public function toggleAvailability(BarangayAsset $asset)
    {
        $asset->update(['is_available' => !$asset->is_available]);
        return back()->with('success', 'Asset availability updated.');
    }

    public function archive(BarangayAsset $asset)
    {
        $asset->update([
            'is_archived' => true,
            'is_available' => false 
        ]);
        return back()->with('success', 'Asset archived successfully.');
    }
}