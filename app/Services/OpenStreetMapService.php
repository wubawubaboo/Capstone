<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapService
{
    /**
     * Convert GPS coordinates to a physical address using OSM Nominatim.
     */
    public function reverseGeocode($latitude, $longitude)
    {
        try {
            // Nominatim requires a User-Agent identifying your application
            $response = Http::withHeaders([
                'User-Agent' => 'SanNicolasEmergencySystem/1.0 (your-email@example.com)'
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'full_address' => $data['display_name'] ?? 'Address not found',
                    // You can extract specific parts to check for jurisdiction boundaries
                    'village' => $data['address']['village'] ?? $data['address']['suburb'] ?? null,
                    'city' => $data['address']['city'] ?? $data['address']['town'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OSM Geocoding Error: ' . $e->getMessage());
            return null;
        }
    }
}