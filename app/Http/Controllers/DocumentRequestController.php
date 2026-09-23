<?php

namespace App\Http\Controllers;

use App\Enums\DocumentRequestStatus;
use App\Exports\DocumentRequestsExport;
use App\Http\Requests\ExportFilterRequest;
use App\Http\Requests\DocumentRequest\StoreDocumentRequestRequest;
use App\Http\Requests\DocumentRequest\UpdateDocumentRequestStatusRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\SystemLog;
use App\Services\DocumentGeneration\DocumentGenerationService;
use App\Services\PhilSmsService;
use App\Support\DocumentFieldCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DocumentRequestController extends Controller
{
    public function create()
    {
        return Inertia::render('Resident/DocumentRequest', [
            'documentTypes' => DocumentType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request)
    {
        $query = DocumentRequest::forBarangay(Auth::user()->barangay_id)
            ->with(['requester', 'documentType']);

        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        $requests = $query->latest()->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('Secretary/DocumentRequests', [
            'requests' => $requests,
            'filters' => $request->only('status'),
            'documentTypes' => DocumentType::withCount('requests')->orderBy('name')->get(),
            'fieldCatalog' => DocumentFieldCatalog::fields(),
        ]);
    }

    public function export(ExportFilterRequest $request)
    {
        $validated = $request->validated();

        $query = DocumentRequest::forBarangay(Auth::user()->barangay_id)
            ->with(['requester', 'documentType']);

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

    public function store(StoreDocumentRequestRequest $request)
    {
        $validated = $request->validated();

        DocumentRequest::create([
            'requester_id' => Auth::user()->id,
            'barangay_id' => Auth::user()->barangay_id,
            'document_type_id' => $validated['document_type_id'],
            'purpose' => $validated['purpose'],
            'status' => DocumentRequestStatus::Pending,
        ]);

        return back()->with('success', 'Document request submitted successfully. You will receive an SMS when it is ready.');
    }

    public function updateStatus(UpdateDocumentRequestStatusRequest $request, DocumentRequest $documentRequest, PhilSmsService $smsService)
    {
        abort_unless($documentRequest->barangay_id === Auth::user()->barangay_id, 404);

        $validated = $request->validated();

        $to = DocumentRequestStatus::from($validated['status']);

        DB::transaction(function () use ($documentRequest, $to) {
            $documentRequest->transitionStatus($to, Auth::user());
        });

        SystemLog::logAction($documentRequest->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Updated Doc Ref #{$documentRequest->reference_no} to {$validated['status']}.");

        if ($to === DocumentRequestStatus::Ready) {
            $documentRequest->load(['documentType', 'requester', 'barangay']);
            $documentName = $documentRequest->documentType->name;
            $residentName = $documentRequest->requester->full_name;
            $message = "Brgy. {$documentRequest->barangay->name}: Hello {$residentName}, your requested {$documentName} is now READY FOR PICKUP at the barangay hall. Please bring a valid ID.";

            $smsService->sendSms($documentRequest->requester->phone_number, $message);
        }

        return back()->with('success', 'Document status updated.');
    }

    public function generate(DocumentRequest $documentRequest, DocumentGenerationService $service)
    {
        abort_unless($documentRequest->barangay_id === Auth::user()->barangay_id, 404);

        $documentRequest->load(['requester', 'barangay', 'documentType']);

        abort_unless($documentRequest->documentType?->hasTemplate(), 422, 'This document type has no template configured.');

        $pdfPath = $service->generate($documentRequest);

        SystemLog::logAction($documentRequest->barangay_id, Auth::id(), 'EXPORT', 'Documents', "Generated document PDF for Doc Ref #{$documentRequest->reference_no}.");

        $filename = $documentRequest->documentType->name . '-' . $documentRequest->reference_no . '.pdf';

        return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
    }
}