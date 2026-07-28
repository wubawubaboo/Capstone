<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangayAsset extends Model
{
    /** @use HasFactory<\Database\Factories\BarangayAssetFactory> */
    use HasFactory;

    protected $guarded = [];
    protected $casts = ['is_available' => 'boolean'];

    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class, 'assigned_asset_id'); }

    // Query Scopes
    public function scopeAvailable($query) { return $query->where('is_available', true); }
    public function scopeByType($query, $type) { return $query->where('asset_type', $type); }

    // State Transitions
    public function dispatch() { $this->update(['is_available' => false]); }
    public function returnToBase() { $this->update(['is_available' => true]); }
}
