<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DocumentRequestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $requests)
    {
    }

    public function collection(): Collection
    {
        return $this->requests;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone Number',
            'Document Type',
            'Purpose',
            'Status',
            'Date Requested',
        ];
    }

    public function map($request): array
    {
        return [
            $request->requester?->full_name ?: 'Unknown',
            $request->requester?->phone_number ?: 'N/A',
            $request->documentType?->name ?: 'Document',
            $request->purpose,
            $request->status,
            optional($request->created_at)->format('Y-m-d H:i'),
        ];
    }
}
