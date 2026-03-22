<?php

namespace App\Services;

use Carbon\Carbon;

class HrStopDetector
{
    public static function detect($tracks, int $minStopSeconds = 300, float $radiusMeters = 15.0): array
    {
        $points = [];
        foreach ($tracks as $t) {
            $lat = isset($t->lat) ? (float) $t->lat : null;
            $lng = isset($t->lng) ? (float) $t->lng : null;
            if ($lat === null || $lng === null) {
                continue;
            }
            $at = !empty($t->tracked_at) ? Carbon::parse((string) $t->tracked_at) : null;
            if (!$at) {
                continue;
            }
            $points[] = [
                'lat' => $lat,
                'lng' => $lng,
                'at' => $at,
            ];
        }

        usort($points, function ($a, $b) {
            return $a['at']->lessThan($b['at']) ? -1 : 1;
        });

        $stops = [];
        $clusterStartIdx = 0;
        for ($i = 1; $i < count($points); $i++) {
            $start = $points[$clusterStartIdx];
            $cur = $points[$i];
            $distM = HrStopDetector::haversineKm($start['lat'], $start['lng'], $cur['lat'], $cur['lng']) * 1000.0;

            if ($distM <= $radiusMeters) {
                continue;
            }

            $clusterEnd = $points[$i - 1];
            $dur = (int) $start['at']->diffInSeconds($clusterEnd['at'], false);
            if ($dur >= $minStopSeconds) {
                $stops[] = [
                    'lat' => $start['lat'],
                    'lng' => $start['lng'],
                    'start_at' => $start['at']->toDateTimeString(),
                    'end_at' => $clusterEnd['at']->toDateTimeString(),
                    'duration_sec' => $dur,
                ];
            }

            $clusterStartIdx = $i;
        }

        if (!empty($points)) {
            $start = $points[$clusterStartIdx];
            $end = $points[count($points) - 1];
            $dur = (int) $start['at']->diffInSeconds($end['at'], false);
            if ($dur >= $minStopSeconds) {
                $stops[] = [
                    'lat' => $start['lat'],
                    'lng' => $start['lng'],
                    'start_at' => $start['at']->toDateTimeString(),
                    'end_at' => $end['at']->toDateTimeString(),
                    'duration_sec' => $dur,
                ];
            }
        }

        return array_slice($stops, 0, 200);
    }

    private static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $r * $c;
    }
}

