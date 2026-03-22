<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HrStopDetector;

class HrAttendanceLiveController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance view|attendance manage']);
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        return view('hr-attendances.live', compact('date'));
    }

    public function data(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());

        $sessions = DB::table('hr_attendance_sessions as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 's.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('s.date', $date)
            ->where('s.status', 'open')
            ->select('s.id', 's.user_id', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name', 's.clock_in_at', 's.clock_in_lat', 's.clock_in_lng')
            ->orderBy('u.name')
            ->get();

        $sessionIds = $sessions->pluck('id')->map(fn ($v) => (int) $v)->all();
        $lastTracks = collect();
        if (!empty($sessionIds)) {
            $lastTracks = DB::table('hr_attendance_tracks as t')
                ->join(DB::raw('(SELECT session_id, MAX(tracked_at) AS max_at FROM hr_attendance_tracks GROUP BY session_id) lt'), function ($join) {
                    $join->on('t.session_id', '=', 'lt.session_id');
                    $join->on('t.tracked_at', '=', 'lt.max_at');
                })
                ->whereIn('t.session_id', $sessionIds)
                ->select('t.session_id', 't.tracked_at', 't.lat', 't.lng', 't.accuracy', 't.speed', 't.is_mock')
                ->get()
                ->keyBy('session_id');
        }

        $out = [];
        foreach ($sessions as $s) {
            $t = $lastTracks->get((int) $s->id);
            $lat = $t ? (float) $t->lat : (!empty($s->clock_in_lat) ? (float) $s->clock_in_lat : null);
            $lng = $t ? (float) $t->lng : (!empty($s->clock_in_lng) ? (float) $s->clock_in_lng : null);
            if ($lat === null || $lng === null) {
                continue;
            }
            $out[] = [
                'session_id' => (int) $s->id,
                'user_id' => (int) $s->user_id,
                'user_name' => (string) $s->user_name,
                'user_email' => (string) $s->user_email,
                'jabatan_name' => $s->jabatan_name,
                'clock_in_at' => $s->clock_in_at,
                'tracked_at' => $t ? (string) $t->tracked_at : null,
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $t ? $t->accuracy : null,
                'speed' => $t ? $t->speed : null,
                'speed_kmh' => $t && $t->speed !== null ? ((float) $t->speed) * 3.6 : null,
                'is_mock' => $t ? (string) $t->is_mock : 'No',
            ];
        }

        return response()->json([
            'date' => $date,
            'count' => count($out),
            'items' => $out,
        ]);
    }

    public function session(Request $request, int $session)
    {
        $row = DB::table('hr_attendance_sessions as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 's.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('s.id', $session)
            ->select('s.id', 's.user_id', 's.date', 's.status', 's.work_type', 's.clock_in_at', 's.clock_out_at', 'u.name as user_name', 'j.name as jabatan_name')
            ->first();
        if (!$row) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $tracks = DB::table('hr_attendance_tracks')
            ->where('session_id', $session)
            ->orderBy('tracked_at')
            ->limit(2000)
            ->get();

        $notes = DB::table('hr_attendance_notes')
            ->where('session_id', $session)
            ->orderBy('noted_at')
            ->limit(200)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => (int) $n->id,
                    'noted_at' => (string) $n->noted_at,
                    'note' => (string) $n->note,
                ];
            })
            ->values()
            ->all();

        $points = [];
        foreach ($tracks as $t) {
            $points[] = [
                'tracked_at' => (string) $t->tracked_at,
                'lat' => (float) $t->lat,
                'lng' => (float) $t->lng,
                'is_mock' => (string) ($t->is_mock ?? 'No'),
            ];
        }

        $step = 1;
        if (count($points) > 600) {
            $step = (int) ceil(count($points) / 600);
        }
        $compactPoints = [];
        for ($i = 0; $i < count($points); $i += $step) {
            $compactPoints[] = $points[$i];
        }
        if (!empty($points) && (empty($compactPoints) || end($compactPoints) !== end($points))) {
            $compactPoints[] = end($points);
        }

        $stops = HrStopDetector::detect($tracks);

        return response()->json([
            'session' => [
                'id' => (int) $row->id,
                'date' => (string) $row->date,
                'status' => (string) $row->status,
                'work_type' => (string) $row->work_type,
                'user_id' => (int) $row->user_id,
                'user_name' => (string) $row->user_name,
                'jabatan_name' => $row->jabatan_name,
                'clock_in_at' => $row->clock_in_at,
                'clock_out_at' => $row->clock_out_at,
            ],
            'points' => $compactPoints,
            'stops' => $stops,
            'notes' => $notes,
        ]);
    }
}
