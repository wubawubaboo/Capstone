<?php

namespace App\Http\Controllers;

use App\Models\BarangayAsset;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class AssetController extends Controller
{
    public function index()
    {
        $assets = BarangayAsset::where('barangay_id', Auth::user()->barangay_id)
                    ->latest()
                    ->paginate(15);
                    
        return Inertia::render('assets.index', compact('assets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_type' => 'required|string|max:100',
        ]);

        $asset = Auth::user()->barangay->assets()->create([
            'asset_name' => $validated['asset_name'],
            'asset_type' => $validated['asset_type'],
            'is_available' => true // Default state
        ]);

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'CREATE', 'Assets', "Added new asset: {$asset->asset_name} ({$asset->asset_type}).");

        return back()->with('success', 'New asset added to inventory successfully.');
    }

    public function toggleAvailability(BarangayAsset $asset)
    {
        // Toggle the current availability status
        if ($asset->is_available) {
            $asset->dispatch();
            $action = 'Dispatched';
        } else {
            $asset->returnToBase();
            $action = 'Returned to Base';
        }

        SystemLog::logAction($asset->barangay_id, Auth::id(), 'UPDATE', 'Assets', "Marked asset {$asset->asset_name} as {$action}.");

        return back()->with('success', "Asset {$asset->asset_name} is now " . ($asset->is_available ? 'Available' : 'Deployed') . ".");
    }

    public function destroy(BarangayAsset $asset)
    {
        $assetName = $asset->asset_name;
        $barangayId = $asset->barangay_id;
        
        $asset->delete();

        SystemLog::logAction($barangayId, Auth::id(), 'DELETE', 'Assets', "Removed asset: {$assetName} from inventory.");

        return back()->with('success', 'Asset removed successfully.');
    }
}