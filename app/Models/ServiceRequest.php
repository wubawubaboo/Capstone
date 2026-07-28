<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRequestFactory> */
    use HasFactory;

    protected $guarded = [];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function asset() { return $this->belongsTo(BarangayAsset::class, 'assigned_asset_id'); }

    // Scopes
    public function scopePending($query) { return $query->where('status', 'Pending'); }
    public function scopeActive($query) { return $query->where('status', 'In Progress'); }

    // State Transitions
    public function assignAsset(BarangayAsset $asset)
    {
        $this->update([
            'assigned_asset_id' => $asset->id,
            'status' => 'In Progress'
        ]);
        $asset->dispatch();
    }

    public function completeService()
    {
        $this->update(['status' => 'Completed']);
        if ($this->asset) {
            $this->asset->returnToBase();
        }
    }
}
