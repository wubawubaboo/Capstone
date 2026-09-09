<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Report;
use App\Models\DocumentRequest;
use App\Models\ServiceRequest;
use App\Models\MediationSchedule;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // 1. Latest Incident (For the UI Card - Removed the SOS filter)
        $latestReport = Report::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->first();

        $mapLocation = null;
        if ($latestReport) {
            $mapLocation = [
                'incident_type' => $latestReport->incident_type,
                'date' => $latestReport->created_at->format('M d, Y h:i A'),
                'is_critical' => $latestReport->incident_type === 'SOS_CRITICAL' // Flag for UI styling
            ];
        }

        // 2. Heatmap Data: Fetch all reports for the density layer
        $heatmapData = Report::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($report) {
                return [
                    (float) $report->latitude,
                    (float) $report->longitude,
                    $report->incident_type === 'SOS_CRITICAL' ? 2 : 1 
                ];
            })->values();

        // 3. Incident Categories 
        $incidentTrends = Report::pluck('incident_type')
            ->countBy()
            ->map(function ($count, $type) {
                return ['incident_type' => $type, 'count' => $count];
            })->values();

        // 4. Document Transactions
        $documentVolumes = DocumentRequest::join('document_types', 'document_requests.document_type_id', '=', 'document_types.id')
            ->pluck('document_types.name')
            ->countBy()
            ->map(function ($count, $name) {
                return ['name' => $name, 'count' => $count];
            })->values();

        // 5. Asset & Service Dispatch
        $serviceStats = ServiceRequest::pluck('service_type')
            ->countBy()
            ->map(function ($count, $type) {
                return ['service_type' => $type, 'count' => $count];
            })->values();

        // 6. Mediation Tracking
        $mediationStats = MediationSchedule::pluck('status')
            ->countBy()
            ->map(function ($count, $status) {
                return ['status' => $status, 'count' => $count];
            })->values();

        return Inertia::render('Secretary/Analytics', [
            'mapLocation' => $mapLocation,
            'heatmapData' => $heatmapData,
            'incidentTrends' => $incidentTrends,
            'documentVolumes' => $documentVolumes,
            'serviceStats' => $serviceStats,
            'mediationStats' => $mediationStats,
        ]);
    }
}