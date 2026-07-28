<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    protected $guarded = [];
    protected $casts = ['is_outside_jurisdiction' => 'boolean'];

    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function incidentBarangay() { return $this->belongsTo(Barangay::class, 'incident_barangay_id'); }
    public function blotterRecord() { return $this->hasOne(BlotterRecord::class); }
    public function attachments() { return $this->hasMany(Attachment::class); }

    // Scopes
    public function scopePending($query) { return $query->where('status', 'Pending'); }
    public function scopeResolved($query) { return $query->where('status', 'Resolved'); }
    public function scopeCrossBoundary($query) { return $query->where('is_outside_jurisdiction', true); }

    // Actions
    public function markAsResolved() { $this->update(['status' => 'Resolved']); }

    public function escalateToBlotter($caseNumber, $cloneUserAsReceiver = false)
    {
        $this->update(['status' => 'Escalated to Blotter']);
        
        return $this->blotterRecord()->create([
            'barangay_id' => $this->incident_barangay_id,
            'case_number' => $caseNumber,
            'receiver_id' => $cloneUserAsReceiver ? Auth::id() : null,
            'official_entry_date' => now()
        ]);
    }
}
