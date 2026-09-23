<?php

namespace App\Models;

use App\Enums\ServiceRequestStatus;
use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'barangay_id',
        'service_type',
        'description',
        'status',
        'assigned_asset_id'
    ];

    protected $casts = [
        'status' => ServiceRequestStatus::class,
    ];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function asset() { return $this->belongsTo(BarangayAsset::class, 'assigned_asset_id'); }
    public function statusHistory() { return $this->hasMany(ServiceRequestStatusHistory::class)->latest('id'); }

    // Scopes
    public function scopePending($query) { return $query->where('status', ServiceRequestStatus::Pending); }
    public function scopeActive($query) { return $query->where('status', ServiceRequestStatus::InProgress); }
    public function scopeForBarangay($query, $barangayId) { return $query->where('barangay_id', $barangayId); }

    /**
     * Moves the request to a new status following the allowed transition map,
     * recording a timeline entry. Throws for illegal transitions.
     */
    public function transitionStatus(ServiceRequestStatus $to, ?User $actor = null, ?string $note = null): void
    {
        $from = $this->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidStatusTransitionException("Service request #{$this->id} cannot move from \"{$from->value}\" to \"{$to->value}\".");
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
