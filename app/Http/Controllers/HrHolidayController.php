<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrHolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance manage']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_holidays')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%');
            })
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return view('hr-holidays.index', compact('rows', 'q'));
    }

    public function create()
    {
        return view('hr-holidays.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:150',
            'type' => 'required|in:national,company',
            'is_active' => 'required|in:Yes,No',
        ]);

        $exists = DB::table('hr_holidays')->where('date', $validated['date'])->exists();
        if ($exists) {
            return back()->withErrors(['date' => 'Tanggal sudah terdaftar.'])->withInput();
        }

        DB::table('hr_holidays')->insert([
            'date' => (string) $validated['date'],
            'name' => trim((string) $validated['name']),
            'type' => (string) $validated['type'],
            'is_active' => (string) $validated['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-holidays.index')->with('success', 'Hari libur berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_holidays')->where('id', $id)->first();
        abort_if(!$row, 404);
        return view('hr-holidays.edit', compact('row'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_holidays')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:150',
            'type' => 'required|in:national,company',
            'is_active' => 'required|in:Yes,No',
        ]);

        $exists = DB::table('hr_holidays')->where('date', $validated['date'])->where('id', '<>', $id)->exists();
        if ($exists) {
            return back()->withErrors(['date' => 'Tanggal sudah terdaftar.'])->withInput();
        }

        DB::table('hr_holidays')->where('id', $id)->update([
            'date' => (string) $validated['date'],
            'name' => trim((string) $validated['name']),
            'type' => (string) $validated['type'],
            'is_active' => (string) $validated['is_active'],
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-holidays.index')->with('success', 'Hari libur berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_holidays')->where('id', $id)->delete();
        return redirect()->route('hr-holidays.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}

