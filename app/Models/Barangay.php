<?php

namespace App\Models;

use App\Services\Geo\PointInPolygon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    /** @use HasFactory<\Database\Factories\BarangayFactory> */
    use HasFactory;

   protected $guarded = [];

   protected $casts = [
       'boundary' => 'array',
   ];

    // Relationships
    public function users() { return $this->hasMany(User::class); }
    public function assets() { return $this->hasMany(BarangayAsset::class); }
    public function reports() { return $this->hasManyThrough(Report::class, User::class); }
    public function documentRequests() { return $this->hasMany(DocumentRequest::class); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class); }
    public function blotters() { return $this->hasMany(BlotterRecord::class); }
    public function systemLogs() { return $this->hasMany(SystemLog::class); }

    public function getAvailableAssets($assetType = null)
    {
        $query = $this->assets()->available();
        if ($assetType) {
            $query->where('asset_type', $assetType);
        }
        return $query->get();
    }

    /** Whether this barangay has any residents/staff or records tied to it, and so cannot be deleted. */
    public function hasDependents(): bool
    {
        return $this->users()->exists() || $this->reports()->exists() || $this->blotters()->exists();
    }

    /**
     * Whether the given coordinates fall inside this barangay's boundary
     * polygon. Returns null (unknown) when no boundary has been fetched yet
     * for this barangay, so callers can fall back to a different check.
     */
    public function containsPoint(float $latitude, float $longitude): ?bool
    {
        if (empty($this->boundary)) {
            return null;
        }

        return PointInPolygon::contains([$latitude, $longitude], $this->boundary);
    }
}

