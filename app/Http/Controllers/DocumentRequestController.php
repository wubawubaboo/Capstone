<?php

namespace App\Http\Controllers;

use App\Exports\DocumentRequestsExport;
use App\Models\DocumentRequest;
use App\Models\SystemLog;
use App\Services\PhilSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentRequest::with(['requester', 'documentType']);

        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        $requests = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Secretary/DocumentRequests', [
            'requests' => $requests,
            'filters' => $request->only('status'),
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = DocumentRequest::with(['requester', 'documentType']);

        $query->when($validated['status'] ?? null, function ($q, $status) {
            return $q->where('status', $status);
        });

        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereBetween('created_at', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59',
            ]);
        }

        $requests = $query->latest()->get();

        $filename = 'document-requests-' . ($validated['start_date'] ?? now()->format('Y-m-d')) . '-to-' . ($validated['end_date'] ?? now()->format('Y-m-d')) . '.xlsx';

        return Excel::download(new DocumentRequestsExport($requests), $filename);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'purpose' => 'required|string|max:500',
        ]);

        DocumentRequest::create([
            'requester_id' => Auth::user()->id,
            'barangay_id' => Auth::user()->barangay_id,
            'document_type_id' => $validated['document_type_id'],
            'purpose' => $validated['purpose'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Document request submitted successfully. You will receive an SMS when it is ready.');
    }

    public function updateStatus(Request $request, DocumentRequest $documentRequest)
    {
        $validated = $request->validate(['status' => 'required|in:Ready,Claimed']);

        if ($validated['status'] === 'Ready') $documentRequest->markAsReady();
        if ($validated['status'] === 'Claimed') $documentRequest->claimDocument();

        SystemLog::logAction($documentRequest->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Updated Doc Ref #{$documentRequest->reference_no} to {$validated['status']}.");

        return back()->with('success', 'Document status updated.');
    }

    public function markAsReady(DocumentRequest $documentRequest, PhilSmsService $smsService)
    {
        $documentRequest->update(['status' => 'ready_for_pickup']);

        $documentName = $documentRequest->documentType->name;
        $residentName = $documentRequest->user->full_name;
        $barangayName = $documentRequest->barangay;
        $message = "Brgy. {$barangayName}: Hello {$residentName}, your requested {$documentName} is now READY FOR PICKUP at the barangay hall. Please bring a valid ID.";

        $smsService->sendSms($documentRequest->user->phone_number, $message);

        return back()->with('success', 'Document marked as ready and SMS sent to the resident.');
    }
}