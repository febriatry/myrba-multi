<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrWorkSchemeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance manage')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_work_schemes')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%');
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('hr-work-schemes.index', compact('rows', 'q'));
    }

    public function create()
    {
        return view('hr-work-schemes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:fixed,flexible,shift',
            'grace_minutes' => 'nullable|integer|min:0|max:1440',
            'break_minutes_default' => 'nullable|integer|min:0|max:600',
            'min_work_minutes_per_day' => 'nullable|integer|min:0|max:2000',
            'overtime_threshold_minutes' => 'nullable|integer|min:0|max:1440',
            'is_active' => 'required|in:Yes,No',
        ]);

        $id = (int) DB::table('hr_work_schemes')->insertGetId([
            'name' => trim((string) $validated['name']),
            'type' => (string) $validated['type'],
            'grace_minutes' => (int) ($validated['grace_minutes'] ?? 0),
            'break_minutes_default' => (int) ($validated['break_minutes_default'] ?? 0),
            'min_work_minutes_per_day' => (int) ($validated['min_work_minutes_per_day'] ?? 0),
            'overtime_threshold_minutes' => (int) ($validated['overtime_threshold_minutes'] ?? 0),
            'is_active' => (string) $validated['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-work-schemes.edit', $id)->with('success', 'Skema berhasil dibuat. Silakan set rule per hari.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_work_schemes')->where('id', $id)->first();
        abort_if(!$row, 404);

        $rules = DB::table('hr_work_scheme_rules')->where('scheme_id', $id)->get()->keyBy('day_of_week');
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return view('hr-work-schemes.edit', compact('row', 'rules', 'days'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_work_schemes')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:fixed,flexible,shift',
            'grace_minutes' => 'nullable|integer|min:0|max:1440',
            'break_minutes_default' => 'nullable|integer|min:0|max:600',
            'min_work_minutes_per_day' => 'nullable|integer|min:0|max:2000',
            'overtime_threshold_minutes' => 'nullable|integer|min:0|max:1440',
            'is_active' => 'required|in:Yes,No',
            'rules' => 'array',
            'rules.*.start_time' => 'nullable|string|max:10',
            'rules.*.end_time' => 'nullable|string|max:10',
            'rules.*.overtime_start_time' => 'nullable|string|max:10',
            'rules.*.flex_window_start' => 'nullable|string|max:10',
            'rules.*.flex_window_end' => 'nullable|string|max:10',
            'rules.*.core_start' => 'nullable|string|max:10',
            'rules.*.core_end' => 'nullable|string|max:10',
            'rules.*.break_minutes' => 'nullable|integer|min:0|max:600',
        ]);

        DB::table('hr_work_schemes')->where('id', $id)->update([
            'name' => trim((string) $validated['name']),
            'type' => (string) $validated['type'],
            'grace_minutes' => (int) ($validated['grace_minutes'] ?? 0),
            'break_minutes_default' => (int) ($validated['break_minutes_default'] ?? 0),
            'min_work_minutes_per_day' => (int) ($validated['min_work_minutes_per_day'] ?? 0),
            'overtime_threshold_minutes' => (int) ($validated['overtime_threshold_minutes'] ?? 0),
            'is_active' => (string) $validated['is_active'],
            'updated_at' => now(),
        ]);

        $rules = $validated['rules'] ?? [];
        foreach ($rules as $day => $data) {
            $day = (int) $day;
            if ($day < 1 || $day > 7) {
                continue;
            }
            $payload = [
                'scheme_id' => $id,
                'day_of_week' => $day,
                'start_time' => !empty($data['start_time']) ? $data['start_time'] : null,
                'end_time' => !empty($data['end_time']) ? $data['end_time'] : null,
                'overtime_start_time' => !empty($data['overtime_start_time']) ? $data['overtime_start_time'] : null,
                'flex_window_start' => !empty($data['flex_window_start']) ? $data['flex_window_start'] : null,
                'flex_window_end' => !empty($data['flex_window_end']) ? $data['flex_window_end'] : null,
                'core_start' => !empty($data['core_start']) ? $data['core_start'] : null,
                'core_end' => !empty($data['core_end']) ? $data['core_end'] : null,
                'break_minutes' => isset($data['break_minutes']) && $data['break_minutes'] !== '' ? (int) $data['break_minutes'] : null,
                'updated_at' => now(),
            ];

            $exists = DB::table('hr_work_scheme_rules')->where('scheme_id', $id)->where('day_of_week', $day)->exists();
            if ($exists) {
                DB::table('hr_work_scheme_rules')->where('scheme_id', $id)->where('day_of_week', $day)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('hr_work_scheme_rules')->insert($payload);
            }
        }

        return redirect()->route('hr-work-schemes.edit', $id)->with('success', 'Skema berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_work_scheme_rules')->where('scheme_id', $id)->delete();
        DB::table('hr_work_schemes')->where('id', $id)->delete();
        return redirect()->route('hr-work-schemes.index')->with('success', 'Skema berhasil dihapus.');
    }

    public function weekendOff(Request $request, int $id, int $day)
    {
        abort_if(!in_array($day, [6, 7], true), 404);

        $row = DB::table('hr_work_schemes')->where('id', $id)->first();
        abort_if(!$row, 404);

        $payload = [
            'scheme_id' => $id,
            'day_of_week' => $day,
            'start_time' => null,
            'end_time' => null,
            'overtime_start_time' => null,
            'flex_window_start' => null,
            'flex_window_end' => null,
            'core_start' => null,
            'core_end' => null,
            'break_minutes' => null,
            'updated_at' => now(),
        ];

        $exists = DB::table('hr_work_scheme_rules')->where('scheme_id', $id)->where('day_of_week', $day)->exists();
        if ($exists) {
            DB::table('hr_work_scheme_rules')->where('scheme_id', $id)->where('day_of_week', $day)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('hr_work_scheme_rules')->insert($payload);
        }

        return redirect()->route('hr-work-schemes.edit', $id)->with('success', 'Rule libur mingguan berhasil diset.');
    }
}
