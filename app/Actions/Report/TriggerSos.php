<?php

namespace App\Actions\Report;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Events\SosTriggered;
use App\Jobs\SendSmsJob;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\OpenStreetMapService;
use Illuminate\Support\Facades\DB;

/**
 * A resident's emergency SOS: records it as a critical report, alerts the
 * staff desks live (SosTriggered, on the emergency-alerts channel), and texts
 * every barangay police officer in the resident's barangay, warning them if
 * the location looks outside the barangay.
 */
class TriggerSos
{
    public function __construct(private OpenStreetMapService $geocoder)
    {
    }

    public function __invoke(User $resident, string $emergencyType, float $latitude, float $longitude): Report
    {
        $barangay = $resident->barangay;
        $barangayName = $barangay->name ?? 'San Nicolas';

        $location = $this->geocoder->reverseGeocode($latitude, $longitude);
        $address = $location ? $location['full_address'] : 'Unknown Location (Check GPS Map)';

        $report = DB::transaction(function () use ($resident, $emergencyType, $latitude, $longitude, $address) {
            $report = Report::create([
                'user_id' => $resident->id,
                'incident_type' => Report::SOS_TYPE,
                'description' => "SOS EMERGENCY: {$emergencyType} at {$address}",
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => ReportStatus::Pending,
            ]);

            SystemLog::logAction($resident->barangay_id, $resident->id, 'CREATE', 'Report', "Triggered SOS emergency ({$emergencyType}) at {$address}.");

            return $report;
        });

        // Alerts go out only once the report is saved.
        broadcast(new SosTriggered($report));

        $message = "URGENT SOS - {$barangayName}: {$emergencyType} reported at {$address}.";
        if ($this->looksOutsideBarangay($resident, $location, $latitude, $longitude)) {
            $message .= ' (WARNING: Potentially outside barangay boundaries).';
        }

        User::where('role', Role::BarangayPolice)
            ->where('barangay_id', $resident->barangay_id)
            ->whereNotNull('phone_number')
            ->pluck('phone_number')
            ->each(fn (string $phone) => SendSmsJob::dispatch($phone, $message));

        return $report;
    }

    private function looksOutsideBarangay(User $resident, ?array $location, float $latitude, float $longitude): bool
    {
        $withinBoundary = $resident->barangay?->containsPoint($latitude, $longitude);

        if ($withinBoundary !== null) {
            // The barangay has a boundary polygon on file: trust it.
            return !$withinBoundary;
        }

        // No polygon yet: fall back to the coarser reverse-geocoded village name.
        $barangayName = $resident->barangay->name ?? 'San Nicolas';

        return $location
            && isset($location['village'])
            && stripos($location['village'], $barangayName) === false;
    }
}
