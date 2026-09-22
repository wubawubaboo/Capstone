<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Report;
use App\Models\DocumentRequest;
use App\Models\ServiceRequest;
use App\Models\MediationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private const HEATMAP_DAYS = 90;

    public function index(Request $request)
    {
        $barangayId = Auth::user()->barangay_id;
        $heatmapSince = now()->subDays(self::HEATMAP_DAYS);

        $heatmapQuery = fn () => Report::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('created_at', '>=', $heatmapSince)
            ->whereHas('user', fn ($q) => $q->where('barangay_id', $barangayId));

        $latestReport = $heatmapQuery()->latest()->first();

        $mapLocation = null;
        if ($latestReport) {
            $mapLocation = [
                'incident_type' => $latestReport->incident_type,
                'date' => $latestReport->created_at->format('M d, Y h:i A'),
                'is_critical' => $latestReport->incident_type === 'SOS_CRITICAL'
            ];
        }

        $heatmapData = $heatmapQuery()
            ->get(['latitude', 'longitude', 'incident_type'])
            ->map(function ($report) {
                return [
                    (float) $report->latitude,
                    (float) $report->longitude,
                    $report->incident_type === 'SOS_CRITICAL' ? 2 : 1
                ];
            })->values();

        $incidentTrends = Report::whereHas('user', fn ($q) => $q->where('barangay_id', $barangayId))
            ->select('incident_type', DB::raw('count(*) as count'))
            ->groupBy('incident_type')
            ->get();

        $documentVolumes = DocumentRequest::join('document_types', 'document_requests.document_type_id', '=', 'document_types.id')
            ->where('document_requests.barangay_id', $barangayId)
            ->select('document_types.name', DB::raw('count(*) as count'))
            ->groupBy('document_types.name')
            ->get();

        $serviceStats = ServiceRequest::where('barangay_id', $barangayId)
            ->select('service_type', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->get();

        $mediationStats = MediationSchedule::whereHas('blotter', function ($q) use ($barangayId) {
                $q->where('barangay_id', $barangayId)->whereDoesntHave('vawcDetail');
            })
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

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