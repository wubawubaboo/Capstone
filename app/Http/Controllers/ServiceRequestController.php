<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\BarangayAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $serviceRequests = ServiceRequest::with(['requester', 'asset'])->latest()->paginate(15);
        $availableAssets = BarangayAsset::where('is_available', true)
                            ->where('barangay_id', Auth::user()->barangay_id)
                            ->get();

        return Inertia::render('Secretary/ServiceRequest', [
            'serviceRequests' => $serviceRequests,
            'availableAssets' => $availableAssets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|string'
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $user->serviceRequests()->create([
            'barangay_id' => $user->barangay_id,
            'service_type' => $validated['service_type'],
            'status' => 'Pending'
        ]);

        return back()->with('success', 'Service requested successfully. Awaiting dispatch.');
    }

    public function assignAsset(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:barangay_assets,id'
        ]);

        $asset = BarangayAsset::findOrFail($validated['asset_id']);
        
        if (!$asset->is_available) {
            return back()->withErrors(['asset_id' => 'This asset is currently deployed.']);
        }

        $serviceRequest->assignAsset($asset);

        return back()->with('success', "{$asset->asset_name} dispatched.");
    }

    public function complete(ServiceRequest $serviceRequest)
    {
        $serviceRequest->completeService();
        return back()->with('success', 'Service marked as completed and asset returned.');
    }
}