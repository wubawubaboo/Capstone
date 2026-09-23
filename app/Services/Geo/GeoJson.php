<?php

namespace App\Services\Geo;

/**
 * Normalizes a GeoJSON geometry (Polygon/MultiPolygon) down to the
 * outer-ring-only, [lat, lng]-ordered format PointInPolygon expects.
 * Interior rings (holes) are intentionally dropped since a containment
 * check doesn't need them for barangay-sized boundaries.
 */
final class GeoJson
{
    /**
     * @return array<int, array<int, array{0: float, 1: float}>>|null
     */
    public static function toRings(?array $geometry): ?array
    {
        if (!$geometry || !isset($geometry['type'], $geometry['coordinates'])) {
            return null;
        }

        $rings = [];

        if ($geometry['type'] === 'Polygon') {
            if ($outerRing = $geometry['coordinates'][0] ?? null) {
                $rings[] = self::toLatLngRing($outerRing);
            }
        } elseif ($geometry['type'] === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $polygon) {
                if ($outerRing = $polygon[0] ?? null) {
                    $rings[] = self::toLatLngRing($outerRing);
                }
            }
        } else {
            return null;
        }

        return $rings === [] ? null : $rings;
    }

    private static function toLatLngRing(array $coordinates): array
    {
        return array_map(fn ($pair) => [$pair[1], $pair[0]], $coordinates);
    }
}
