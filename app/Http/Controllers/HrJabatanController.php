<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrJabatanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance manage')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_jabatans')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%');
            })
            ->orderBy('rank_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hr-jabatans.index', compact('rows', 'q'));
    }

    public function create()
    {
        return view('hr-jabatans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rank_order' => 'nullable|integer|min:0|max:1000000',
        ]);

        DB::table('hr_jabatans')->insert([
            'name' => trim((string) $validated['name']),
            'rank_order' => (int) ($validated['rank_order'] ?? 0),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-jabatans.index')->with('success', 'Jabatan berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_jabatans')->where('id', $id)->first();
        abort_if(!$row, 404);
        return view('hr-jabatans.edit', compact('row'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_jabatans')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rank_order' => 'nullable|integer|min:0|max:1000000',
        ]);

        DB::table('hr_jabatans')->where('id', $id)->update([
            'name' => trim((string) $validated['name']),
            'rank_order' => (int) ($validated['rank_order'] ?? 0),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-jabatans.index')->with('success', 'Jabatan berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_jabatans')->where('id', $id)->delete();
        return redirect()->route('hr-jabatans.index')->with('success', 'Jabatan berhasil dihapus.');
    }
}

