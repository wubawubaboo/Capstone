<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    /** @use HasFactory<\Database\Factories\BarangayFactory> */
    use HasFactory;

   protected $guarded = [];

    // Relationships
    public function users() { return $this->hasMany(User::class); }
    public function assets() { return $this->hasMany(BarangayAsset::class); }
    public function reports() { return $this->hasMany(Report::class, 'incident_barangay_id'); }
    public function documentRequests() { return $this->hasMany(DocumentRequest::class); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class); }
    public function blotters() { return $this->hasMany(BlotterRecord::class); }
    public function systemLogs() { return $this->hasMany(SystemLog::class); }

    // Utility Methods
    public function getAvailableAssets($assetType = null)
    {
        $query = $this->assets()->available();
        if ($assetType) {
            $query->where('asset_type', $assetType);
        }
        return $query->get();
    }
}

