<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance manage')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->leftJoin('hr_work_schemes as ws', 'ep.work_scheme_id', '=', 'ws.id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('u.name', 'like', '%' . $q . '%')
                        ->orWhere('u.email', 'like', '%' . $q . '%')
                        ->orWhere('ep.employee_code', 'like', '%' . $q . '%');
                });
            })
            ->select('ep.*', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name', 'ws.name as scheme_name')
            ->orderByDesc('ep.id')
            ->paginate(20)
            ->withQueryString();

        return view('hr-employees.index', compact('rows', 'q'));
    }

    public function create(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $users = DB::table('users')
            ->leftJoin('hr_employee_profiles as ep', 'users.id', '=', 'ep.user_id')
            ->whereNull('ep.id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('users.name', 'like', '%' . $q . '%')
                        ->orWhere('users.email', 'like', '%' . $q . '%');
                });
            })
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->limit(200)
            ->get();

        $jabatans = DB::table('hr_jabatans')->orderBy('rank_order')->orderBy('name')->get();
        $schemes = DB::table('hr_work_schemes')->where('is_active', 'Yes')->orderBy('name')->get();

        return view('hr-employees.create', compact('users', 'jabatans', 'schemes', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'employee_code' => 'nullable|string|max:50',
            'jabatan_id' => 'nullable|integer|min:1',
            'work_scheme_id' => 'nullable|integer|min:1',
            'is_active' => 'required|in:Yes,No',
            'joined_at' => 'nullable|date',
            'salary_type' => 'required|in:monthly,daily',
            'monthly_salary' => 'nullable|integer|min:0',
            'daily_salary' => 'nullable|integer|min:0',
            'overtime_rate_per_hour' => 'nullable|integer|min:0',
            'operational_daily_rate' => 'nullable|integer|min:0',
            'mandatory_deduction_type' => 'required|in:fixed,percent',
            'mandatory_deduction_value' => 'nullable|integer|min:0|max:1000000000',
            'weekly_off_days' => 'array',
            'weekly_off_days.*' => 'integer|min:1|max:7',
        ]);

        if ((string) $validated['mandatory_deduction_type'] === 'percent') {
            $validated['mandatory_deduction_value'] = min(100, (int) ($validated['mandatory_deduction_value'] ?? 0));
        }

        $exists = DB::table('hr_employee_profiles')->where('user_id', (int) $validated['user_id'])->exists();
        if ($exists) {
            return back()->withErrors(['user_id' => 'User sudah terdaftar sebagai karyawan.'])->withInput();
        }

        DB::table('hr_employee_profiles')->insert([
            'user_id' => (int) $validated['user_id'],
            'employee_code' => !empty($validated['employee_code']) ? trim((string) $validated['employee_code']) : null,
            'jabatan_id' => !empty($validated['jabatan_id']) ? (int) $validated['jabatan_id'] : null,
            'work_scheme_id' => !empty($validated['work_scheme_id']) ? (int) $validated['work_scheme_id'] : null,
            'is_active' => (string) $validated['is_active'],
            'joined_at' => $validated['joined_at'] ?? null,
            'salary_type' => (string) $validated['salary_type'],
            'monthly_salary' => (int) ($validated['monthly_salary'] ?? 0),
            'daily_salary' => (int) ($validated['daily_salary'] ?? 0),
            'overtime_rate_per_hour' => (int) ($validated['overtime_rate_per_hour'] ?? 0),
            'operational_daily_rate' => (int) ($validated['operational_daily_rate'] ?? 0),
            'mandatory_deduction_type' => (string) $validated['mandatory_deduction_type'],
            'mandatory_deduction_value' => (int) ($validated['mandatory_deduction_value'] ?? 0),
            'weekly_off_days' => !empty($validated['weekly_off_days']) ? json_encode(array_values($validated['weekly_off_days'])) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-employees.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $row = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.id', $id)
            ->select('ep.*', 'u.name as user_name', 'u.email as user_email')
            ->first();
        abort_if(!$row, 404);

        $jabatans = DB::table('hr_jabatans')->orderBy('rank_order')->orderBy('name')->get();
        $schemes = DB::table('hr_work_schemes')->orderBy('name')->get();

        return view('hr-employees.edit', compact('row', 'jabatans', 'schemes'));
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('hr_employee_profiles')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'employee_code' => 'nullable|string|max:50',
            'jabatan_id' => 'nullable|integer|min:1',
            'work_scheme_id' => 'nullable|integer|min:1',
            'is_active' => 'required|in:Yes,No',
            'joined_at' => 'nullable|date',
            'salary_type' => 'required|in:monthly,daily',
            'monthly_salary' => 'nullable|integer|min:0',
            'daily_salary' => 'nullable|integer|min:0',
            'overtime_rate_per_hour' => 'nullable|integer|min:0',
            'operational_daily_rate' => 'nullable|integer|min:0',
            'mandatory_deduction_type' => 'required|in:fixed,percent',
            'mandatory_deduction_value' => 'nullable|integer|min:0|max:1000000000',
            'weekly_off_days' => 'array',
            'weekly_off_days.*' => 'integer|min:1|max:7',
        ]);

        if ((string) $validated['mandatory_deduction_type'] === 'percent') {
            $validated['mandatory_deduction_value'] = min(100, (int) ($validated['mandatory_deduction_value'] ?? 0));
        }

        DB::table('hr_employee_profiles')->where('id', $id)->update([
            'employee_code' => !empty($validated['employee_code']) ? trim((string) $validated['employee_code']) : null,
            'jabatan_id' => !empty($validated['jabatan_id']) ? (int) $validated['jabatan_id'] : null,
            'work_scheme_id' => !empty($validated['work_scheme_id']) ? (int) $validated['work_scheme_id'] : null,
            'is_active' => (string) $validated['is_active'],
            'joined_at' => $validated['joined_at'] ?? null,
            'salary_type' => (string) $validated['salary_type'],
            'monthly_salary' => (int) ($validated['monthly_salary'] ?? 0),
            'daily_salary' => (int) ($validated['daily_salary'] ?? 0),
            'overtime_rate_per_hour' => (int) ($validated['overtime_rate_per_hour'] ?? 0),
            'operational_daily_rate' => (int) ($validated['operational_daily_rate'] ?? 0),
            'mandatory_deduction_type' => (string) $validated['mandatory_deduction_type'],
            'mandatory_deduction_value' => (int) ($validated['mandatory_deduction_value'] ?? 0),
            'weekly_off_days' => !empty($validated['weekly_off_days']) ? json_encode(array_values($validated['weekly_off_days'])) : null,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-employees.index')->with('success', 'Karyawan berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        DB::table('hr_employee_profiles')->where('id', $id)->delete();
        return redirect()->route('hr-employees.index')->with('success', 'Karyawan berhasil dihapus.');
    }
}
