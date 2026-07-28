<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DocumentRequestController extends Controller
{
    public function index()
    {
        $requests = DocumentRequest::with(['requester', 'documentType'])->latest()->paginate(15);
        return Inertia::render('documents.index', compact('requests'));
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
        $validated = $request->validate(['status' => 'required|in:Paid,Ready,Claimed']);

        if ($validated['status'] === 'Paid') $documentRequest->markAsPaid();
        if ($validated['status'] === 'Ready') $documentRequest->markAsReady();
        if ($validated['status'] === 'Claimed') $documentRequest->claimDocument();

        SystemLog::logAction($documentRequest->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Updated Doc Ref #{$documentRequest->reference_no} to {$validated['status']}.");

        return back()->with('success', 'Document status updated.');
    }
}