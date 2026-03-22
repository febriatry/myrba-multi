<?php

namespace App\Http\Controllers;

use App\Services\HrAttendanceCalculator;
use App\Services\HrStopDetector;
use App\Services\HrTrackAnalyzer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance view')->only(['index', 'show']);
        $this->middleware('permission:attendance manage')->only(['create', 'store', 'edit', 'update', 'destroy', 'review']);
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('hr_attendance_sessions as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 's.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('s.date', $date)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('u.name', 'like', '%' . $q . '%')
                        ->orWhere('u.email', 'like', '%' . $q . '%');
                });
            })
            ->select('s.*', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name')
            ->orderBy('u.name')
            ->paginate(20)
            ->withQueryString();

        return view('hr-attendances.index', compact('rows', 'date', 'q'));
    }

    public function create(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        return view('hr-attendances.create', compact('employees', 'date'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date',
            'work_type' => 'required|in:normal,overtime',
            'status' => 'required|in:open,closed',
            'clock_in_at' => 'nullable|date',
            'clock_out_at' => 'nullable|date',
            'clock_in_lat' => 'nullable|numeric',
            'clock_in_lng' => 'nullable|numeric',
            'clock_out_lat' => 'nullable|numeric',
            'clock_out_lng' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
        ]);

        $clockInAt = $this->normalizeDateTime($validated['clock_in_at'] ?? null);
        $clockOutAt = $this->normalizeDateTime($validated['clock_out_at'] ?? null);

        $calc = HrAttendanceCalculator::calculate(
            (int) $validated['user_id'],
            (string) $validated['date'],
            $clockInAt,
            $clockOutAt
        );
        $overtimeMinutes = (int) ($calc['overtime_minutes'] ?? 0);
        $overtimeReviewStatus = $overtimeMinutes > 0 ? 'pending' : null;

        DB::table('hr_attendance_sessions')->insert([
            'user_id' => (int) $validated['user_id'],
            'date' => (string) $validated['date'],
            'work_type' => (string) $validated['work_type'],
            'status' => (string) $validated['status'],
            'clock_in_at' => $clockInAt,
            'clock_out_at' => $clockOutAt,
            'clock_in_lat' => $validated['clock_in_lat'] ?? null,
            'clock_in_lng' => $validated['clock_in_lng'] ?? null,
            'clock_out_lat' => $validated['clock_out_lat'] ?? null,
            'clock_out_lng' => $validated['clock_out_lng'] ?? null,
            'scheduled_start_at' => $calc['scheduled_start_at'],
            'scheduled_end_at' => $calc['scheduled_end_at'],
            'break_minutes' => (int) ($calc['break_minutes'] ?? 0),
            'late_minutes' => (int) ($calc['late_minutes'] ?? 0),
            'work_minutes' => (int) ($calc['work_minutes'] ?? 0),
            'overtime_minutes' => $overtimeMinutes,
            'overtime_review_status' => $overtimeReviewStatus,
            'overtime_approved_minutes' => 0,
            'undertime_minutes' => (int) ($calc['undertime_minutes'] ?? 0),
            'created_by' => auth()->id(),
            'notes' => !empty($validated['notes']) ? trim((string) $validated['notes']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendances.index', ['date' => $validated['date']])->with('success', 'Absensi berhasil dibuat.');
    }

    public function show(int $id)
    {
        $row = DB::table('hr_attendance_sessions as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 's.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('s.id', $id)
            ->select('s.*', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name')
            ->first();
        abort_if(!$row, 404);

        $tracks = DB::table('hr_attendance_tracks')
            ->where('session_id', $id)
            ->orderBy('tracked_at')
            ->limit(2000)
            ->get();

        $notes = DB::table('hr_attendance_notes')
            ->where('session_id', $id)
            ->orderBy('noted_at')
            ->limit(200)
            ->get();

        $analysis = HrTrackAnalyzer::analyze($tracks);
        $stops = HrStopDetector::detect($tracks);
        $sites = DB::table('hr_attendance_sites')->where('is_active', 'Yes')->orderBy('name')->get();
        $siteCheck = $this->siteCheck($row, $sites);

        return view('hr-attendances.show', compact('row', 'tracks', 'analysis', 'notes', 'stops', 'sites', 'siteCheck'));
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$row, 404);

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        return view('hr-attendances.edit', compact('row', 'employees'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date',
            'work_type' => 'required|in:normal,overtime',
            'status' => 'required|in:open,closed',
            'clock_in_at' => 'nullable|date',
            'clock_out_at' => 'nullable|date',
            'clock_in_lat' => 'nullable|numeric',
            'clock_in_lng' => 'nullable|numeric',
            'clock_out_lat' => 'nullable|numeric',
            'clock_out_lng' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
        ]);

        $clockInAt = $this->normalizeDateTime($validated['clock_in_at'] ?? null);
        $clockOutAt = $this->normalizeDateTime($validated['clock_out_at'] ?? null);

        $calc = HrAttendanceCalculator::calculate(
            (int) $validated['user_id'],
            (string) $validated['date'],
            $clockInAt,
            $clockOutAt
        );
        $overtimeMinutes = (int) ($calc['overtime_minutes'] ?? 0);
        $overtimeChanged = (int) ($row->overtime_minutes ?? 0) !== $overtimeMinutes;
        $overtimeReviewStatus = $overtimeMinutes > 0 ? 'pending' : null;

        $payload = [
            'user_id' => (int) $validated['user_id'],
            'date' => (string) $validated['date'],
            'work_type' => (string) $validated['work_type'],
            'status' => (string) $validated['status'],
            'clock_in_at' => $clockInAt,
            'clock_out_at' => $clockOutAt,
            'clock_in_lat' => $validated['clock_in_lat'] ?? null,
            'clock_in_lng' => $validated['clock_in_lng'] ?? null,
            'clock_out_lat' => $validated['clock_out_lat'] ?? null,
            'clock_out_lng' => $validated['clock_out_lng'] ?? null,
            'scheduled_start_at' => $calc['scheduled_start_at'],
            'scheduled_end_at' => $calc['scheduled_end_at'],
            'break_minutes' => (int) ($calc['break_minutes'] ?? 0),
            'late_minutes' => (int) ($calc['late_minutes'] ?? 0),
            'work_minutes' => (int) ($calc['work_minutes'] ?? 0),
            'overtime_minutes' => $overtimeMinutes,
            'undertime_minutes' => (int) ($calc['undertime_minutes'] ?? 0),
            'notes' => !empty($validated['notes']) ? trim((string) $validated['notes']) : null,
            'updated_at' => now(),
        ];
        if ($overtimeChanged) {
            $payload['overtime_review_status'] = $overtimeReviewStatus;
            $payload['overtime_approved_minutes'] = 0;
            $payload['overtime_reviewed_by'] = null;
            $payload['overtime_reviewed_at'] = null;
            $payload['overtime_review_note'] = null;
        }
        DB::table('hr_attendance_sessions')->where('id', $id)->update($payload);

        return redirect()->route('hr-attendances.index', ['date' => $validated['date']])->with('success', 'Absensi berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $row = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        DB::table('hr_attendance_tracks')->where('session_id', $id)->delete();
        DB::table('hr_attendance_notes')->where('session_id', $id)->delete();
        DB::table('hr_operational_dailies')->where('session_id', $id)->delete();
        DB::table('hr_attendance_sessions')->where('id', $id)->delete();
        return redirect()->route('hr-attendances.index', ['date' => $row->date ?? now()->toDateString()])->with('success', 'Absensi berhasil dihapus.');
    }

    public function approve(Request $request, int $id)
    {
        $row = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$row, 404);

        $sites = DB::table('hr_attendance_sites')->where('is_active', 'Yes')->get();
        $check = $this->siteCheck($row, $sites);
        if (!empty($sites) && !($check['ok'] ?? false)) {
            return redirect()->route('hr-attendances.show', $id)->withErrors(['attendance' => 'Lokasi absensi di luar titik absensi.']);
        }

        DB::table('hr_attendance_sessions')->where('id', $id)->update([
            'review_status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendances.show', $id)->with('success', 'Absensi berhasil divalidasi.');
    }

    public function reject(Request $request, int $id)
    {
        $row = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$row, 404);

        DB::table('hr_attendance_sessions')->where('id', $id)->update([
            'review_status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'overtime_review_status' => null,
            'overtime_approved_minutes' => 0,
            'overtime_reviewed_by' => null,
            'overtime_reviewed_at' => null,
            'overtime_review_note' => null,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendances.show', $id)->with('success', 'Absensi ditolak.');
    }

    public function importTracks(Request $request, int $id)
    {
        $session = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$session, 404);

        $validated = $request->validate([
            'file' => 'required|file',
        ]);

        $path = $validated['file']->getRealPath();
        if (!$path) {
            return back()->withErrors(['file' => 'File tidak valid.']);
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            return back()->withErrors(['file' => 'Gagal membaca file.']);
        }

        $firstLine = fgets($fp);
        if ($firstLine === false) {
            fclose($fp);
            return back()->withErrors(['file' => 'File kosong.']);
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($fp);

        $header = null;
        $rows = [];
        $lineNo = 0;
        while (($cols = fgetcsv($fp, 0, $delimiter)) !== false) {
            $lineNo++;
            if ($lineNo === 1) {
                $maybeHeader = array_map(fn ($v) => strtolower(trim((string) $v)), $cols);
                if (in_array('tracked_at', $maybeHeader, true) || in_array('lat', $maybeHeader, true) || in_array('lng', $maybeHeader, true)) {
                    $header = $maybeHeader;
                    continue;
                }
            }

            $get = function (string $key, int $idx) use ($header, $cols) {
                if ($header) {
                    $pos = array_search($key, $header, true);
                    if ($pos === false) {
                        return null;
                    }
                    return $cols[$pos] ?? null;
                }
                return $cols[$idx] ?? null;
            };

            $trackedAtRaw = $get('tracked_at', 0);
            $latRaw = $get('lat', 1);
            $lngRaw = $get('lng', 2);
            if ($trackedAtRaw === null || $latRaw === null || $lngRaw === null) {
                continue;
            }

            $trackedAtRaw = trim((string) $trackedAtRaw);
            $latRaw = trim((string) $latRaw);
            $lngRaw = trim((string) $lngRaw);
            if ($trackedAtRaw === '' || $latRaw === '' || $lngRaw === '') {
                continue;
            }

            try {
                $trackedAt = Carbon::parse(str_replace('T', ' ', $trackedAtRaw))->toDateTimeString();
            } catch (\Throwable $e) {
                continue;
            }

            if (!is_numeric($latRaw) || !is_numeric($lngRaw)) {
                continue;
            }
            $lat = (float) $latRaw;
            $lng = (float) $lngRaw;

            $accuracy = $get('accuracy', 3);
            $speed = $get('speed', 4);
            $bearing = $get('bearing', 5);
            $isMockRaw = $get('is_mock', 6);
            $isMock = 'No';
            if ($isMockRaw !== null) {
                $v = strtolower(trim((string) $isMockRaw));
                if ($v === 'yes' || $v === 'true' || $v === '1') {
                    $isMock = 'Yes';
                }
            }

            $rows[] = [
                'session_id' => $id,
                'tracked_at' => $trackedAt,
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => ($accuracy !== null && $accuracy !== '' && is_numeric($accuracy)) ? (float) $accuracy : null,
                'speed' => ($speed !== null && $speed !== '' && is_numeric($speed)) ? (float) $speed : null,
                'bearing' => ($bearing !== null && $bearing !== '' && is_numeric($bearing)) ? (float) $bearing : null,
                'is_mock' => $isMock,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= 2000) {
                break;
            }
        }
        fclose($fp);

        if (empty($rows)) {
            return back()->withErrors(['file' => 'Tidak ada baris valid yang bisa diimport. Format minimal: tracked_at,lat,lng']);
        }

        DB::table('hr_attendance_tracks')->where('session_id', $id)->delete();
        DB::table('hr_attendance_tracks')->insert($rows);

        $analysis = HrTrackAnalyzer::analyze(DB::table('hr_attendance_tracks')->where('session_id', $id)->orderBy('tracked_at')->limit(2000)->get());
        $flags = [
            'imported' => true,
            'mock_points' => $analysis['mock_points'] ?? 0,
            'anomaly_events' => $analysis['event_count'] ?? 0,
        ];
        DB::table('hr_attendance_sessions')->where('id', $id)->update([
            'flags' => json_encode($flags),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendances.show', $id)->with('success', 'Tracking berhasil diimport.');
    }

    public function clearTracks(Request $request, int $id)
    {
        $session = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$session, 404);

        DB::table('hr_attendance_tracks')->where('session_id', $id)->delete();
        DB::table('hr_attendance_notes')->where('session_id', $id)->delete();
        DB::table('hr_attendance_sessions')->where('id', $id)->update([
            'flags' => null,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendances.show', $id)->with('success', 'Tracking berhasil dibersihkan.');
    }

    public function addNote(Request $request, int $id)
    {
        $session = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$session, 404);

        $validated = $request->validate([
            'noted_at' => 'nullable|date',
            'note' => 'required|string|max:500',
        ]);

        DB::table('hr_attendance_notes')->insert([
            'session_id' => $id,
            'noted_at' => !empty($validated['noted_at']) ? $this->normalizeDateTime($validated['noted_at']) : now()->toDateTimeString(),
            'note' => trim((string) $validated['note']),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendances.show', $id)->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function deleteNote(Request $request, int $id, int $noteId)
    {
        $note = DB::table('hr_attendance_notes')->where('id', $noteId)->where('session_id', $id)->first();
        abort_if(!$note, 404);
        DB::table('hr_attendance_notes')->where('id', $noteId)->delete();
        return redirect()->route('hr-attendances.show', $id)->with('success', 'Catatan berhasil dihapus.');
    }

    public function sampleCsv()
    {
        $csv = "tracked_at,lat,lng,accuracy,speed,bearing,is_mock\n";
        $csv .= now()->subMinutes(5)->format('Y-m-d H:i:s') . ",-6.200000,106.816666,10,0,0,No\n";
        $csv .= now()->subMinutes(4)->format('Y-m-d H:i:s') . ",-6.200500,106.817000,12,0,0,No\n";
        $csv .= now()->subMinutes(3)->format('Y-m-d H:i:s') . ",-6.201000,106.817500,8,0,0,No\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_tracks_sample.csv"',
        ]);
    }

    private function normalizeDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        return str_replace('T', ' ', $s);
    }

    private function siteCheck($attendance, $sites): array
    {
        $lat = isset($attendance->clock_in_lat) ? (float) $attendance->clock_in_lat : null;
        $lng = isset($attendance->clock_in_lng) ? (float) $attendance->clock_in_lng : null;
        if ($lat === null || $lng === null) {
            return ['ok' => false, 'distance_m' => null, 'site' => null];
        }

        $best = null;
        foreach ($sites as $s) {
            $dist = $this->haversineKm($lat, $lng, (float) $s->lat, (float) $s->lng) * 1000.0;
            if ($best === null || $dist < $best['distance_m']) {
                $best = [
                    'site' => $s,
                    'distance_m' => $dist,
                    'radius_m' => (int) $s->radius_m,
                ];
            }
        }
        if ($best === null) {
            return ['ok' => false, 'distance_m' => null, 'site' => null];
        }
        $best['ok'] = $best['distance_m'] <= $best['radius_m'];
        $best['distance_m'] = (int) round($best['distance_m']);
        return $best;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
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
