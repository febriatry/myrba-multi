<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrOperationalDailyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance payroll']);
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('hr_operational_dailies as od')
            ->join('users as u', 'od.user_id', '=', 'u.id')
            ->where('od.date', $date)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('u.name', 'like', '%' . $q . '%')
                        ->orWhere('u.email', 'like', '%' . $q . '%')
                        ->orWhere('od.note', 'like', '%' . $q . '%');
                });
            })
            ->select('od.*', 'u.name as user_name', 'u.email as user_email')
            ->orderByDesc('od.id')
            ->paginate(20)
            ->withQueryString();

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        return view('hr-operational-dailies.index', compact('rows', 'employees', 'date', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date',
            'amount' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        DB::table('hr_operational_dailies')->insert([
            'user_id' => (int) $validated['user_id'],
            'date' => (string) $validated['date'],
            'amount' => (int) $validated['amount'],
            'source' => 'manual',
            'note' => !empty($validated['note']) ? trim((string) $validated['note']) : null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Operasional harian berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_operational_dailies as od')
            ->join('users as u', 'od.user_id', '=', 'u.id')
            ->where('od.id', $id)
            ->select('od.*', 'u.name as user_name', 'u.email as user_email')
            ->first();
        abort_if(!$row, 404);
        abort_if(!empty($row->source) && (string) $row->source === 'auto', 403);

        return view('hr-operational-dailies.edit', compact('row'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_operational_dailies')->where('id', $id)->first();
        abort_if(!$row, 404);
        abort_if(!empty($row->source) && (string) $row->source === 'auto', 403);

        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        DB::table('hr_operational_dailies')->where('id', $id)->update([
            'date' => (string) $validated['date'],
            'amount' => (int) $validated['amount'],
            'note' => !empty($validated['note']) ? trim((string) $validated['note']) : null,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Operasional harian berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $row = DB::table('hr_operational_dailies')->where('id', $id)->first();
        abort_if(!$row, 404);
        abort_if(!empty($row->source) && (string) $row->source === 'auto', 403);
        DB::table('hr_operational_dailies')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Operasional harian berhasil dihapus.');
    }
}
