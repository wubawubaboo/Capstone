<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BlottersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $blotters)
    {
    }

    public function collection(): Collection
    {
        return $this->blotters;
    }

    public function headings(): array
    {
        return [
            'Case Number',
            'Complainant',
            'Respondent',
            'Incident Type',
            'Status',
            'Date Filed',
        ];
    }

    public function map($blotter): array
    {
        return [
            $blotter->case_number,
            $blotter->complainant_name ?: ($blotter->report?->user?->full_name ?: 'Anonymous'),
            $blotter->receiver?->full_name ?: ($blotter->receiver_name ?: 'Unknown'),
            $blotter->incident_type ?: ($blotter->report?->incident_type ?: 'N/A'),
            $blotter->status,
            optional($blotter->official_entry_date ?? $blotter->created_at)->format('Y-m-d H:i'),
        ];
    }
}
