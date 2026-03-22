<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrPotonganHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance payroll']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'other');
        $date = (string) $request->query('date', now()->toDateString());
        $q = trim((string) $request->query('q', ''));

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        if ($tab === 'sanction') {
            $rows = DB::table('hr_sanctions as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->where('s.date', $date)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($q2) use ($q) {
                        $q2->where('u.name', 'like', '%' . $q . '%')
                            ->orWhere('u.email', 'like', '%' . $q . '%')
                            ->orWhere('s.type', 'like', '%' . $q . '%')
                            ->orWhere('s.note', 'like', '%' . $q . '%');
                    });
                })
                ->select('s.*', 'u.name as user_name', 'u.email as user_email')
                ->orderByDesc('s.id')
                ->paginate(20)
                ->withQueryString();
        } else {
            $rows = DB::table('hr_deductions as d')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->where('d.date', $date)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($q2) use ($q) {
                        $q2->where('u.name', 'like', '%' . $q . '%')
                            ->orWhere('u.email', 'like', '%' . $q . '%')
                            ->orWhere('d.type', 'like', '%' . $q . '%')
                            ->orWhere('d.note', 'like', '%' . $q . '%');
                    });
                })
                ->select('d.*', 'u.name as user_name', 'u.email as user_email')
                ->orderByDesc('d.id')
                ->paginate(20)
                ->withQueryString();
        }

        return view('hr-potongan.index', compact('tab', 'date', 'q', 'employees', 'rows'));
    }
}

