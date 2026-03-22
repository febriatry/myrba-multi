<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrShiftRosterController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance manage')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $userId = (int) $request->query('user_id', 0);

        $rows = DB::table('hr_shift_rosters as r')
            ->join('users as u', 'r.user_id', '=', 'u.id')
            ->join('hr_shift_definitions as s', 'r.shift_id', '=', 's.id')
            ->when($date !== '', function ($q) use ($date) {
                $q->where('r.date', $date);
            })
            ->when($userId > 0, function ($q) use ($userId) {
                $q->where('r.user_id', $userId);
            })
            ->select('r.*', 'u.name as user_name', 's.name as shift_name', 's.start_time', 's.end_time')
            ->orderBy('u.name')
            ->paginate(20)
            ->withQueryString();

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        return view('hr-shift-rosters.index', compact('rows', 'date', 'userId', 'employees'));
    }

    public function create()
    {
        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        $shifts = DB::table('hr_shift_definitions')->where('is_active', 'Yes')->orderBy('name')->get();

        return view('hr-shift-rosters.create', compact('employees', 'shifts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date',
            'shift_id' => 'required|integer|min:1',
            'status' => 'required|in:planned,swapped,approved',
            'notes' => 'nullable|string|max:255',
        ]);

        $exists = DB::table('hr_shift_rosters')->where('user_id', (int) $validated['user_id'])->where('date', $validated['date'])->exists();
        if ($exists) {
            return back()->withErrors(['date' => 'Roster untuk user & tanggal tersebut sudah ada.'])->withInput();
        }

        DB::table('hr_shift_rosters')->insert([
            'user_id' => (int) $validated['user_id'],
            'date' => $validated['date'],
            'shift_id' => (int) $validated['shift_id'],
            'status' => (string) $validated['status'],
            'notes' => !empty($validated['notes']) ? trim((string) $validated['notes']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-shift-rosters.index', ['date' => $validated['date']])->with('success', 'Jadwal shift berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_shift_rosters')->where('id', $id)->first();
        abort_if(!$row, 404);

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        $shifts = DB::table('hr_shift_definitions')->orderBy('name')->get();

        return view('hr-shift-rosters.edit', compact('row', 'employees', 'shifts'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_shift_rosters')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date',
            'shift_id' => 'required|integer|min:1',
            'status' => 'required|in:planned,swapped,approved',
            'notes' => 'nullable|string|max:255',
        ]);

        $exists = DB::table('hr_shift_rosters')
            ->where('user_id', (int) $validated['user_id'])
            ->where('date', $validated['date'])
            ->where('id', '<>', $id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['date' => 'Roster untuk user & tanggal tersebut sudah ada.'])->withInput();
        }

        DB::table('hr_shift_rosters')->where('id', $id)->update([
            'user_id' => (int) $validated['user_id'],
            'date' => $validated['date'],
            'shift_id' => (int) $validated['shift_id'],
            'status' => (string) $validated['status'],
            'notes' => !empty($validated['notes']) ? trim((string) $validated['notes']) : null,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-shift-rosters.index', ['date' => $validated['date']])->with('success', 'Jadwal shift berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $row = DB::table('hr_shift_rosters')->where('id', $id)->first();
        DB::table('hr_shift_rosters')->where('id', $id)->delete();
        return redirect()->route('hr-shift-rosters.index', ['date' => $row->date ?? now()->toDateString()])->with('success', 'Jadwal shift berhasil dihapus.');
    }
}

