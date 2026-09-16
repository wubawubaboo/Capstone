<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ServiceRequestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $serviceRequests)
    {
    }

    public function collection(): Collection
    {
        return $this->serviceRequests;
    }

    public function headings(): array
    {
        return [
            'Request ID',
            'Requester',
            'Location/Address',
            'Service Type',
            'Status',
            'Dispatched Asset',
            'Date Requested',
        ];
    }

    public function map($request): array
    {
        return [
            'REQ-' . $request->id,
            $request->requester?->full_name ?: 'Unknown',
            $request->requester?->address ?: 'N/A',
            $request->service_type,
            $request->status,
            $request->asset?->asset_name ?: 'N/A',
            optional($request->created_at)->format('Y-m-d H:i'),
        ];
    }
}
