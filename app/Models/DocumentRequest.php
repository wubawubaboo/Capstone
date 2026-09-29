<?php

namespace App\Models;

use App\Enums\DocumentRequestStatus;
use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'barangay_id',
        'document_type_id',
        'purpose',
        'status',
        'reference_no',
    ];

    protected $casts = [
        'status' => DocumentRequestStatus::class,
    ];

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
    public function statusHistory() { return $this->hasMany(DocumentRequestStatusHistory::class)->latest('id'); }

    // Query Scopes
    public function scopePending($query) { return $query->where('status', DocumentRequestStatus::Pending); }
    public function scopeForBarangay($query, $barangayId) { return $query->where('barangay_id', $barangayId); }

    /**
     * Moves the request to a new status following the allowed transition map,
     * recording a timeline entry. Throws for illegal transitions.
     */
    public function transitionStatus(DocumentRequestStatus $to, ?User $actor = null, ?string $note = null): void
    {
        $from = $this->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidStatusTransitionException("Document request #{$this->reference_no} cannot move from \"{$from->value}\" to \"{$to->value}\".");
        }

        DB::transaction(function () use ($from, $to, $actor, $note) {
            $this->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $actor?->id,
                'note' => $note,
            ]);

            $this->update(['status' => $to]);
        });
    }
}
