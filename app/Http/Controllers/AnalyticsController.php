<?php

namespace App\Http\Controllers;

use App\Enums\CaseDesk;
use App\Models\BlotterRecord;
use App\Models\DocumentRequest;
use App\Models\Report;
use App\Models\ServiceRequest;
use App\Services\IncidentAnalytics;
use App\Support\AnalyticsPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** The secretary desk's incident monitoring dashboard. */
class AnalyticsController extends Controller
{
    public function __construct(private IncidentAnalytics $analytics) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $period = AnalyticsPeriod::fromRequest($request);
        $window = [$period->start, $period->end];

        $dashboard = $this->analytics->dashboard(
            Report::visibleTo($user),
            CaseDesk::Secretary->scopeCases(BlotterRecord::forBarangay($user->barangay_id)),
            $period,
            ['map' => true, 'sos' => true],
        );

        $documentVolumes = DocumentRequest::join('document_types', 'document_requests.document_type_id', '=', 'document_types.id')
            ->where('document_requests.barangay_id', $user->barangay_id)
            ->whereBetween('document_requests.created_at', $window)
            ->select('document_types.name', DB::raw('count(*) as count'))
            ->groupBy('document_types.name')
            ->orderByDesc('count')
            ->get();

        $serviceStats = ServiceRequest::where('barangay_id', $user->barangay_id)
            ->whereBetween('created_at', $window)
            ->select('service_type as name', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->orderByDesc('count')
            ->get();

        return Inertia::render('Secretary/Analytics', [
            'dashboard' => $dashboard,
            'barangayName' => $user->barangay?->name,
            'boundary' => $user->barangay?->boundary,
            'documentVolumes' => $documentVolumes,
            'serviceStats' => $serviceStats,
        ]);
    }
}
