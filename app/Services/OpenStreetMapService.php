<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapService
{
    public function reverseGeocode($latitude, $longitude)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent(),
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

    /**
     * Build the User-Agent Nominatim's usage policy requires. Falls back to
     * the app URL (and logs a warning) if OSM_CONTACT_EMAIL isn't set, since
     * an identifying contact is required for requests not to be blocked.
     */
    private function userAgent(): string
    {
        $contact = config('services.osm.contact');

        if (!$contact) {
            Log::warning('OSM_CONTACT_EMAIL is not set; falling back to APP_URL for the Nominatim User-Agent. Set OSM_CONTACT_EMAIL to a real contact to avoid being blocked.');
            $contact = config('app.url', 'no-contact-configured');
        }

        $appName = config('app.name', 'Laravel');

        return "{$appName}/1.0 ({$contact})";
    }
}