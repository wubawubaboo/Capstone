<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $reports)
    {
    }

    public function collection(): Collection
    {
        return $this->reports;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Reporter',
            'Contact',
            'Incident Type',
            'Description',
            'Status',
        ];
    }

    public function map($report): array
    {
        return [
            optional($report->created_at)->format('Y-m-d H:i'),
            $report->user?->full_name ?: 'N/A',
            $report->user?->phone_number ?: 'N/A',
            $report->incident_type === 'SOS_CRITICAL' ? 'URGENT SOS' : $report->incident_type,
            $report->description,
            $report->status,
        ];
    }
}
