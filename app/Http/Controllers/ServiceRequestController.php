<?php

namespace App\Http\Controllers;

use App\Enums\ServiceRequestStatus;
use App\Exports\ServiceRequestsExport;
use App\Http\Requests\ExportFilterRequest;
use App\Http\Requests\ServiceRequest\AssignAssetRequest;
use App\Http\Requests\ServiceRequest\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\BarangayAsset;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceRequest::forBarangay(Auth::user()->barangay_id)
            ->with(['requester', 'asset']);

        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        $serviceRequests = $query->latest()->paginate(self::PER_PAGE)->withQueryString();
        
        $availableAssets = BarangayAsset::where('is_available', true)
                            ->where('barangay_id', Auth::user()->barangay_id)
                            ->get();

        return Inertia::render('Secretary/ServiceRequest', [
            'serviceRequests' => $serviceRequests,
            'availableAssets' => $availableAssets,
            'filters' => $request->only('status')
        ]);
    }

    public function export(ExportFilterRequest $request)
    {
        $validated = $request->validated();

        $query = ServiceRequest::forBarangay(Auth::user()->barangay_id)
            ->with(['requester', 'asset']);

        $query->when($validated['status'] ?? null, function ($q, $status) {
            return $q->where('status', $status);
        });

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereBetween('created_at', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59',
            ]);
        }

        $serviceRequests = $query->latest()->get();

        $filename = 'service-requests-' . ($validated['start_date'] ?? now()->format('Y-m-d')) . '-to-' . ($validated['end_date'] ?? now()->format('Y-m-d')) . '.xlsx';

        return Excel::download(new ServiceRequestsExport($serviceRequests), $filename);
    }

    public function store(StoreServiceRequestRequest $request)
    {
        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $user->serviceRequests()->create([
            'requester_id' => $user->id,
            'barangay_id'  => $user->barangay_id,
            'service_type' => $validated['service_type'],
            'description'  => $validated['description'],
            'status'       => ServiceRequestStatus::Pending,
        ]);

        return back()->with('success', 'Service requested successfully. Awaiting dispatch.');
    }

    public function assignAsset(AssignAssetRequest $request, ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->barangay_id === Auth::user()->barangay_id, 404);

        $validated = $request->validated();

        $asset = BarangayAsset::where('barangay_id', Auth::user()->barangay_id)
            ->findOrFail($validated['asset_id']);

        if (!$asset->is_available) {
            return back()->withErrors(['asset_id' => 'This asset is currently deployed.']);
        }

        DB::transaction(function () use ($serviceRequest, $asset) {
            $serviceRequest->update(['assigned_asset_id' => $asset->id]);
            $serviceRequest->transitionStatus(ServiceRequestStatus::InProgress, Auth::user(), "Asset \"{$asset->asset_name}\" dispatched.");
            $asset->update(['is_available' => false]);
        });

        SystemLog::record('UPDATE', 'Service Request', "Dispatched asset \"{$asset->asset_name}\" for service request #{$serviceRequest->id}.");

        return back()->with('success', "{$asset->asset_name} dispatched.");
    }

    public function complete(ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->barangay_id === Auth::user()->barangay_id, 404);

        DB::transaction(function () use ($serviceRequest) {
            $serviceRequest->transitionStatus(ServiceRequestStatus::Completed, Auth::user(), 'Service completed.');

            if ($serviceRequest->assigned_asset_id) {
                $asset = BarangayAsset::find($serviceRequest->assigned_asset_id);
                if ($asset) {
                    $asset->update(['is_available' => true]);
                }
            }
        });

        SystemLog::record('UPDATE', 'Service Request', "Completed service request #{$serviceRequest->id}.");

        return back()->with('success', 'Service marked as completed and asset returned.');
    }
}