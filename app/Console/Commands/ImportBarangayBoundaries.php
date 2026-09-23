<?php

namespace App\Console\Commands;

use App\Models\Barangay;
use App\Services\Geo\GeoJson;
use Illuminate\Console\Command;

class ImportBarangayBoundaries extends Command
{
    /**
     * php artisan barangay:import-boundaries storage/app/geo/gapan-city-barangays.geojson
     * php artisan barangay:import-boundaries storage/app/geo/barangays.geojson --city="Gapan" --dry-run
     */
    protected $signature = 'barangay:import-boundaries
        {file : Path to a GeoJSON FeatureCollection of barangay boundaries (OCHA COD-AB schema: ADM4_EN/ADM3_EN/ADM2_EN properties)}
        {--name-property=ADM4_EN : Feature property holding the barangay name}
        {--city-property=ADM3_EN : Feature property holding the city/municipality name}
        {--city= : Only consider features whose city-property value contains this (case-insensitive); use when importing a nationwide file}
        {--force : Overwrite barangays that already have a boundary}
        {--dry-run : Preview matches without saving anything}';

    protected $description = 'Bulk-import barangay boundary polygons from a local GeoJSON file (e.g. PSA/NAMRIA administrative boundaries)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $geojson = json_decode(file_get_contents($path), true);
        $features = $geojson['features'] ?? null;
        if (!is_array($features)) {
            $this->error('File is not a valid GeoJSON FeatureCollection.');
            return self::FAILURE;
        }

        $nameProperty = $this->option('name-property');
        $cityProperty = $this->option('city-property');
        $cityFilter = $this->option('city');

        $barangays = Barangay::when(!$this->option('force'), fn ($q) => $q->whereNull('boundary'))->get();

        if ($barangays->isEmpty()) {
            $this->info('No barangays to import (use --force to overwrite existing boundaries).');
            return self::SUCCESS;
        }

        $normalize = fn (string $s) => strtolower(trim(preg_replace('/\s*\(.*?\)\s*/', '', $s)));

        // Index candidate features by normalized name; a name can legitimately
        // repeat across different cities, so keep every match for a clean
        // "ambiguous" report rather than silently picking one.
        $byName = [];
        foreach ($features as $feature) {
            $name = $feature['properties'][$nameProperty] ?? null;
            if ($name === null) {
                continue;
            }

            if ($cityFilter && !str_contains(
                strtolower($feature['properties'][$cityProperty] ?? ''),
                strtolower($cityFilter)
            )) {
                continue;
            }

            $byName[$normalize($name)][] = $feature;
        }

        $imported = 0;
        $unmatched = [];
        $ambiguous = [];

        foreach ($barangays as $barangay) {
            $candidates = $byName[$normalize($barangay->name)] ?? [];

            if (count($candidates) === 0) {
                $unmatched[] = $barangay->name;
                continue;
            }

            if (count($candidates) > 1) {
                $ambiguous[$barangay->name] = array_map(
                    fn ($f) => $f['properties'][$cityProperty] ?? '?',
                    $candidates
                );
                continue;
            }

            $rings = GeoJson::toRings($candidates[0]['geometry']);
            if (!$rings) {
                $unmatched[] = "{$barangay->name} (matched but geometry could not be parsed)";
                continue;
            }

            if (!$this->option('dry-run')) {
                $barangay->update([
                    'boundary' => $rings,
                    'boundary_fetched_at' => now(),
                ]);
            }

            $imported++;
            $suffix = $this->option('dry-run') ? ' [dry-run]' : '';
            $this->line("  -> {$barangay->name}: matched (" . count($rings) . " ring(s)){$suffix}");
        }

        $this->info("\nImported: {$imported}/{$barangays->count()}");

        if ($unmatched) {
            $this->warn('No match found for: ' . implode(', ', $unmatched));
        }

        if ($ambiguous) {
            $this->warn('Ambiguous (same name matched in multiple cities) — narrow with --city or rename, then re-run:');
            foreach ($ambiguous as $name => $cities) {
                $this->line("  {$name}: " . implode(', ', $cities));
            }
        }

        return self::SUCCESS;
    }
}
