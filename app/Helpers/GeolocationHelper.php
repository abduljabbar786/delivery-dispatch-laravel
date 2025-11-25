<?php

namespace App\Helpers;

class GeolocationHelper
{
    /**
     * Calculate the distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of the first point
     * @param float $lng1 Longitude of the first point
     * @param float $lat2 Latitude of the second point
     * @param float $lng2 Longitude of the second point
     * @return float Distance in meters
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
