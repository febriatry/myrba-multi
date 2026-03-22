<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrAttendanceSiteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance manage']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_attendance_sites')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%');
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hr-attendance-sites.index', compact('rows', 'q'));
    }

    public function create()
    {
        return view('hr-attendance-sites.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius_m' => 'required|integer|min:10|max:5000',
            'is_active' => 'required|in:Yes,No',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::table('hr_attendance_sites')->insert([
            'name' => trim((string) $validated['name']),
            'lat' => (float) $validated['lat'],
            'lng' => (float) $validated['lng'],
            'radius_m' => (int) $validated['radius_m'],
            'is_active' => (string) $validated['is_active'],
            'notes' => !empty($validated['notes']) ? trim((string) $validated['notes']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendance-sites.index')->with('success', 'Titik absensi berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_attendance_sites')->where('id', $id)->first();
        abort_if(!$row, 404);
        return view('hr-attendance-sites.edit', compact('row'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_attendance_sites')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius_m' => 'required|integer|min:10|max:5000',
            'is_active' => 'required|in:Yes,No',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::table('hr_attendance_sites')->where('id', $id)->update([
            'name' => trim((string) $validated['name']),
            'lat' => (float) $validated['lat'],
            'lng' => (float) $validated['lng'],
            'radius_m' => (int) $validated['radius_m'],
            'is_active' => (string) $validated['is_active'],
            'notes' => !empty($validated['notes']) ? trim((string) $validated['notes']) : null,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-attendance-sites.index')->with('success', 'Titik absensi berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_attendance_sites')->where('id', $id)->delete();
        return redirect()->route('hr-attendance-sites.index')->with('success', 'Titik absensi berhasil dihapus.');
    }
}

