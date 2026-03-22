<?php

namespace App\Services;

use Carbon\Carbon;

class HrTrackAnalyzer
{
    public static function analyze($tracks): array
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
                'is_mock' => ((string) ($t->is_mock ?? 'No')) === 'Yes',
            ];
        }

        usort($points, function ($a, $b) {
            return $a['at']->lessThan($b['at']) ? -1 : 1;
        });

        $mockCount = 0;
        foreach ($points as $p) {
            if ($p['is_mock']) {
                $mockCount++;
            }
        }

        $events = [];
        $maxSpeedKmh = 0.0;
        for ($i = 1; $i < count($points); $i++) {
            $prev = $points[$i - 1];
            $cur = $points[$i];
            $sec = max(0, (int) $prev['at']->diffInSeconds($cur['at'], false));
            if ($sec <= 0) {
                continue;
            }
            $distKm = self::haversineKm($prev['lat'], $prev['lng'], $cur['lat'], $cur['lng']);
            $speedKmh = $distKm / ($sec / 3600.0);
            $maxSpeedKmh = max($maxSpeedKmh, $speedKmh);

            $isSpeed = $speedKmh >= 150;
            $isTeleport = $distKm >= 1.0 && $sec <= 30;
            if ($isSpeed || $isTeleport) {
                $events[] = [
                    'from_at' => $prev['at']->toDateTimeString(),
                    'to_at' => $cur['at']->toDateTimeString(),
                    'distance_km' => round($distKm, 3),
                    'duration_sec' => $sec,
                    'speed_kmh' => round($speedKmh, 1),
                    'type' => $isTeleport ? 'teleport' : 'speed',
                ];
            }
        }

        $needsReview = $mockCount > 0 || count($events) > 0;

        return [
            'total_points' => count($points),
            'mock_points' => $mockCount,
            'max_speed_kmh' => round($maxSpeedKmh, 1),
            'event_count' => count($events),
            'needs_review' => $needsReview,
            'events' => array_slice($events, 0, 200),
        ];
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

