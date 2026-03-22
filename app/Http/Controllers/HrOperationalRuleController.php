<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrOperationalRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance payroll']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_operational_rules as r')
            ->leftJoin('users as u', 'r.user_id', '=', 'u.id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('u.name', 'like', '%' . $q . '%')
                        ->orWhere('r.note', 'like', '%' . $q . '%');
                });
            })
            ->select('r.*', 'u.name as user_name')
            ->orderByDesc('r.is_active')
            ->orderByRaw('CASE WHEN r.user_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN r.date IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByDesc('r.id')
            ->paginate(20)
            ->withQueryString();

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name')
            ->orderBy('u.name')
            ->get();

        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return view('hr-operational-rules.index', compact('rows', 'employees', 'days', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:global,user',
            'user_id' => 'nullable|integer|min:1',
            'mode' => 'required|in:date,weekday',
            'date' => 'nullable|date',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'amount' => 'required|integer|min:0',
            'is_active' => 'required|in:Yes,No',
            'note' => 'nullable|string|max:255',
        ]);

        $userId = null;
        if ((string) $validated['scope'] === 'user') {
            $userId = !empty($validated['user_id']) ? (int) $validated['user_id'] : null;
        }

        $date = null;
        $dow = null;
        if ((string) $validated['mode'] === 'date') {
            $date = (string) ($validated['date'] ?? '');
            if ($date === '') {
                return back()->withErrors(['date' => 'Tanggal wajib diisi.'])->withInput();
            }
        } else {
            $dow = (int) ($validated['day_of_week'] ?? 0);
            if ($dow < 1 || $dow > 7) {
                return back()->withErrors(['day_of_week' => 'Hari wajib diisi.'])->withInput();
            }
        }

        DB::table('hr_operational_rules')->insert([
            'user_id' => $userId,
            'date' => $date,
            'day_of_week' => $dow,
            'amount' => (int) $validated['amount'],
            'is_active' => (string) $validated['is_active'],
            'note' => !empty($validated['note']) ? trim((string) $validated['note']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Rule operasional berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_operational_rules')->where('id', $id)->first();
        abort_if(!$row, 404);

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name')
            ->orderBy('u.name')
            ->get();

        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return view('hr-operational-rules.edit', compact('row', 'employees', 'days'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_operational_rules')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|min:1',
            'date' => 'nullable|date',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'amount' => 'required|integer|min:0',
            'is_active' => 'required|in:Yes,No',
            'note' => 'nullable|string|max:255',
        ]);

        DB::table('hr_operational_rules')->where('id', $id)->update([
            'user_id' => !empty($validated['user_id']) ? (int) $validated['user_id'] : null,
            'date' => !empty($validated['date']) ? (string) $validated['date'] : null,
            'day_of_week' => !empty($validated['day_of_week']) ? (int) $validated['day_of_week'] : null,
            'amount' => (int) $validated['amount'],
            'is_active' => (string) $validated['is_active'],
            'note' => !empty($validated['note']) ? trim((string) $validated['note']) : null,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Rule operasional berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_operational_rules')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Rule operasional berhasil dihapus.');
    }
}
