<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrOperationalHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance payroll']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'daily');

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
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

        $data = [
            'tab' => $tab,
            'employees' => $employees,
            'days' => $days,
        ];

        if ($tab === 'rules') {
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
            $data['q'] = $q;
            $data['rows'] = $rows;
        } else {
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
            $data['date'] = $date;
            $data['q'] = $q;
            $data['rows'] = $rows;
        }

        return view('hr-operational.index', $data);
    }
}

