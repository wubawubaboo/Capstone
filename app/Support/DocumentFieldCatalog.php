<?php

namespace App\Support;

use App\Models\DocumentRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Single source of truth for the requester/document fields secretaries can
 * map onto a document template (docx merge tokens or image field boxes).
 * Keys here must match the ${key} tokens secretaries type into .docx
 * templates and the field_key values stored in field_positions_json.
 */
class DocumentFieldCatalog
{
    public static function fields(): array
    {
        return [
            'full_name' => 'Full Name',
            'phone_number' => 'Phone Number',
            'address' => 'Address',
            'date_of_birth' => 'Date of Birth',
            'age' => 'Age',
            'start_of_residency' => 'Residency Start Year',
            'years_of_residency' => 'Years of Residency',
            'purpose' => 'Purpose',
            'reference_no' => 'Reference No.',
            'date_issued' => 'Date Issued',
            'date_day' => 'Date Issued - Day',
            'date_month' => 'Date Issued - Month',
            'date_month_short' => 'Date Issued - Month (Short)',
            'date_year' => 'Date Issued - Year',
            'barangay_name' => 'Barangay Name',
            'document_type_name' => 'Document Type',
            'base_fee' => 'Base Fee',
            'secretary_name' => 'Secretary Name',
        ];
    }

    public static function resolve(DocumentRequest $documentRequest): array
    {
        $requester = $documentRequest->requester;

        return [
            'full_name' => $requester->full_name,
            'phone_number' => $requester->phone_number,
            'address' => $requester->address,
            'date_of_birth' => $requester->date_of_birth?->format('F d, Y') ?? 'N/A',
            'age' => $requester->getAge() ?? 'N/A',
            'start_of_residency' => $requester->start_of_residency ?? 'N/A',
            'years_of_residency' => $requester->getYearsOfResidency() ?? 'N/A',
            'purpose' => $documentRequest->purpose,
            'reference_no' => $documentRequest->reference_no,
            'date_issued' => now()->format('F d, Y'),
            'date_day' => now()->format('d'),
            'date_month' => now()->format('F'),
            'date_month_short' => now()->format('M'),
            'date_year' => now()->format('Y'),
            'barangay_name' => $documentRequest->barangay->name,
            'document_type_name' => $documentRequest->documentType->name,
            'base_fee' => $documentRequest->documentType->base_fee !== null
                ? number_format((float) $documentRequest->documentType->base_fee, 2)
                : 'Free',
            'secretary_name' => Auth::user()?->full_name ?? '',
        ];
    }
}
