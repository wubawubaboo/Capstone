<?php

namespace App\Services\Geo;

final class PointInPolygon
{
    /**
     * Ray-casting point-in-polygon test against a single ring.
     *
     * @param array{0: float, 1: float} $point [lat, lng]
     * @param array<int, array{0: float, 1: float}> $ring Ring points as [lat, lng] pairs, in order.
     */
    public static function ringContains(array $point, array $ring): bool
    {
        [$lat, $lng] = $point;
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$latI, $lngI] = $ring[$i];
            [$latJ, $lngJ] = $ring[$j];

            // Only edges that straddle the point's latitude can be crossed by
            // the eastward ray we're casting from the point.
            if (($latI > $lat) === ($latJ > $lat)) {
                continue;
            }

            $intersectLng = $lngI + ($lat - $latI) * ($lngJ - $lngI) / ($latJ - $latI);

            if ($lng < $intersectLng) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * True if the point falls inside any ring of a (multi)polygon boundary.
     *
     * @param array{0: float, 1: float} $point [lat, lng]
     * @param array<int, array<int, array{0: float, 1: float}>> $rings One outer ring per polygon part.
     */
    public static function contains(array $point, array $rings): bool
    {
        foreach ($rings as $ring) {
            if (self::ringContains($point, $ring)) {
                return true;
            }
        }

        return false;
    }
}
