<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentRequestFactory> */
    use HasFactory;

protected $guarded = [];

    // Auto-generate reference number on creation
    protected static function booted()
    {
        static::creating(function ($request) {
            if (empty($request->reference_no)) {
                $request->reference_no = 'DOC-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function documentType() { return $this->belongsTo(DocumentType::class); }

    // Query Scopes
    public function scopePending($query) { return $query->where('status', 'Pending'); }
    public function scopeForBarangay($query, $barangayId) { return $query->where('barangay_id', $barangayId); }

    // State Transitions
    public function markAsPaid() { $this->update(['status' => 'Paid']); }
    public function markAsReady() { $this->update(['status' => 'Ready']); }
    public function claimDocument() { $this->update(['status' => 'Claimed']); }
}
