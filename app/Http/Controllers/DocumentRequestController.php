<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\SystemLog;
use App\Services\PhilSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DocumentRequestController extends Controller
{
    public function index()
    {
    $requests = DocumentRequest::with(['requester', 'documentType'])->latest()->paginate(15);
    return Inertia::render('Secretary/DocumentRequests', compact('requests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'purpose' => 'required|string',
            'barangay_id' => 'required|exists:barangays,id'
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $docRequest = $user->documentRequests()->create($validated + ['status' => 'Pending']);

        return back()->with('success', "Request submitted. Reference No: {$docRequest->reference_no}");
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
        $message = "Brgy. San Nicolas: Hello {$residentName}, your requested {$documentName} is now READY FOR PICKUP at the barangay hall. Please bring a valid ID.";

        $smsService->sendSms($documentRequest->user->phone_number, $message);

        return back()->with('success', 'Document marked as ready and SMS sent to the resident.');
    }
}