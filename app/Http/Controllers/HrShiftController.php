<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance manage')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_shift_definitions')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hr-shifts.index', compact('rows', 'q'));
    }

    public function create()
    {
        return view('hr-shifts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'break_minutes' => 'nullable|integer|min:0|max:600',
            'is_active' => 'required|in:Yes,No',
        ]);

        DB::table('hr_shift_definitions')->insert([
            'name' => trim((string) $validated['name']),
            'start_time' => (string) $validated['start_time'],
            'end_time' => (string) $validated['end_time'],
            'break_minutes' => (int) ($validated['break_minutes'] ?? 0),
            'is_active' => (string) $validated['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-shifts.index')->with('success', 'Shift berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_shift_definitions')->where('id', $id)->first();
        abort_if(!$row, 404);
        return view('hr-shifts.edit', compact('row'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_shift_definitions')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'break_minutes' => 'nullable|integer|min:0|max:600',
            'is_active' => 'required|in:Yes,No',
        ]);

        DB::table('hr_shift_definitions')->where('id', $id)->update([
            'name' => trim((string) $validated['name']),
            'start_time' => (string) $validated['start_time'],
            'end_time' => (string) $validated['end_time'],
            'break_minutes' => (int) ($validated['break_minutes'] ?? 0),
            'is_active' => (string) $validated['is_active'],
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-shifts.index')->with('success', 'Shift berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_shift_definitions')->where('id', $id)->delete();
        return redirect()->route('hr-shifts.index')->with('success', 'Shift berhasil dihapus.');
    }
}

