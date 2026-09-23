<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asset\StoreAssetRequest;
use App\Models\BarangayAsset;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index()
    {
        $assets = BarangayAsset::where('barangay_id', Auth::user()->barangay_id)
            ->where('is_archived', false)
            ->latest()
            ->paginate(self::PER_PAGE);

        return Inertia::render('Secretary/AssetManagement', [
            'assets' => $assets
        ]);
    }

    public function store(StoreAssetRequest $request)
    {
        $validated = $request->validated();

        $asset = BarangayAsset::create([
            'barangay_id' => Auth::user()->barangay_id,
            'asset_name' => $validated['asset_name'],
            'asset_type' => $validated['asset_type'],
            'is_available' => true,
            'is_archived' => false,
        ]);

        SystemLog::record('CREATE', 'Asset', "Added asset \"{$asset->asset_name}\".");

        return back()->with('success', 'New asset added successfully.');
    }

    public function toggleAvailability(BarangayAsset $asset)
    {
        abort_unless($asset->barangay_id === Auth::user()->barangay_id, 404);

        $asset->update(['is_available' => !$asset->is_available]);

        SystemLog::record('UPDATE', 'Asset', "Marked asset \"{$asset->asset_name}\" as " . ($asset->is_available ? 'available' : 'unavailable') . '.');

        return back()->with('success', 'Asset availability updated.');
    }

    public function archive(BarangayAsset $asset)
    {
        abort_unless($asset->barangay_id === Auth::user()->barangay_id, 404);

        $asset->update([
            'is_archived' => true,
            'is_available' => false
        ]);

        SystemLog::record('UPDATE', 'Asset', "Archived asset \"{$asset->asset_name}\".");

        return back()->with('success', 'Asset archived successfully.');
    }
}