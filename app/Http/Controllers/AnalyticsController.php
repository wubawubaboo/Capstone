<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\DocumentRequest;
use App\Models\BlotterRecord;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard for the Secretary module.
     */
    public function index(Request $request)
    {
        // 1. Quick KPI Statistics
        $stats = [
            'document_requests' => DocumentRequest::count(),
            'pending_documents' => DocumentRequest::where('status', 'pending')->count(),
            'blotter_records'   => BlotterRecord::count(),
            'service_requests'  => ServiceRequest::count(),
        ];

        // 2. Document Requests Trend (Last 6 Months)
        $months = collect(range(5, 0))->map(function($i) {
            return Carbon::now()->startOfMonth()->subMonths($i);
        });

        $docs = DocumentRequest::where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())->get();
        
        $documentTrends = $months->map(function ($month) use ($docs) {
            $monthString = $month->format('M Y');
            return [
                'name'  => $monthString,
                'total' => $docs->filter(fn($d) => $d->created_at->format('M Y') === $monthString)->count(),
            ];
        })->values();

        // 3. Blotter Status Distribution
        $blotterStatusData = collect([]);
        // $blotterStatusData = BlotterRecord::select('status')
        //     ->get()
        //     ->groupBy('status')
        //     ->map(function ($items, $status) {
        //         return [
        //             'name'  => ucfirst($status),
        //             'value' => $items->count()
        //         ];
        //     })->values();

        return Inertia::render('Secretary/Analytics', [
            'auth' => [
                'user' => $request->user(),
            ],
            'stats'             => $stats,
            'documentTrends'    => $documentTrends,
            'blotterStatusData' => $blotterStatusData
        ]);
    }
}