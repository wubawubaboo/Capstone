<?php

namespace App\Models;

use App\Enums\MediationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediationSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\MediationScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'blotter_record_id',
        'meeting_number',
        'scheduled_date',
        'status',
        'notes',
        'summons_pdf_path',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'status' => MediationStatus::class,
    ];

    public function blotter() { return $this->belongsTo(BlotterRecord::class, 'blotter_record_id'); }

    // Scopes
    public function scopeUpcoming($query) { return $query->where('scheduled_date', '>', now()); }

    // Transitions
    public function markAsResolved() { $this->update(['status' => MediationStatus::Resolved]); }
    public function markAsFailed() { $this->update(['status' => MediationStatus::Failed]); }
}
