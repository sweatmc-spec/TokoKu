<?php

namespace App\Services;

use App\Models\WorkLocation;
use Illuminate\Support\Collection;

class GeolocationService
{
    /** Jari-jari bumi dalam meter */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Hitung jarak antara dua koordinat (dalam meter) menggunakan formula Haversine.
     */
    public function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Cari work_location aktif terdekat dari satu titik koordinat,
     * dan tentukan apakah titik itu valid (di dalam radius lokasi tersebut).
     *
     * @return array{location: WorkLocation, distance: float, is_valid: bool}|null
     */
    public function findNearestActiveLocation(float $latitude, float $longitude): ?array
    {
        /** @var Collection<int, WorkLocation> $locations */
        $locations = WorkLocation::where('is_active', true)->get();

        if ($locations->isEmpty()) {
            return null;
        }

        $nearest = null;
        $minDistance = null;

        foreach ($locations as $location) {
            $distance = $this->distanceInMeters(
                $latitude,
                $longitude,
                $location->latitude,
                $location->longitude
            );

            if ($minDistance === null || $distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $location;
            }
        }

        return [
            'location' => $nearest,
            'distance' => $minDistance,
            'is_valid' => $minDistance <= $nearest->radius_meters,
        ];
    }
}