<?php

namespace App\Models;

use App\Enums\BlotterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VawcDetail extends Model
{
    /** @use HasFactory<\Database\Factories\VawcDetailFactory> */
    use HasFactory;

    protected $fillable = [
        'blotter_record_id',
        'officer_in_charge_id',
        'confidential_notes',
    ];

    protected function casts(): array
    {
        return [
            'confidential_notes' => 'encrypted',
        ];
    }

    public function blotter() { return $this->belongsTo(BlotterRecord::class, 'blotter_record_id'); }
    public function officer() { return $this->belongsTo(User::class, 'officer_in_charge_id'); }

    /** Whether the case was settled, i.e. resolved at the barangay rather than escalated or still open. */
    public function isSettled(): bool
    {
        return $this->blotter?->status === BlotterStatus::Resolved;
    }
}
