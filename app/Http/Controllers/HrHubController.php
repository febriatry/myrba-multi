<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HrHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance view|attendance manage|attendance payroll']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'karyawan',
                'label' => 'Master Karyawan',
                'permission' => 'attendance manage',
                'src' => route('hr-employees.index', ['embed' => 1]),
            ],
            [
                'key' => 'jabatan',
                'label' => 'Master Jabatan',
                'permission' => 'attendance manage',
                'src' => route('hr-jabatans.index', ['embed' => 1]),
            ],
            [
                'key' => 'skema',
                'label' => 'Skema Jam Kerja',
                'permission' => 'attendance manage',
                'src' => route('hr-work-schemes.index', ['embed' => 1]),
            ],
            [
                'key' => 'shift',
                'label' => 'Master Shift',
                'permission' => 'attendance manage',
                'src' => route('hr-shifts.index', ['embed' => 1]),
            ],
            [
                'key' => 'roster',
                'label' => 'Jadwal Shift',
                'permission' => 'attendance manage',
                'src' => route('hr-shift-rosters.index', ['embed' => 1]),
            ],
            [
                'key' => 'titik',
                'label' => 'Titik Absensi',
                'permission' => 'attendance manage',
                'src' => route('hr-attendance-sites.index', ['embed' => 1]),
            ],
            [
                'key' => 'libur',
                'label' => 'Hari Libur',
                'permission' => 'attendance manage',
                'src' => route('hr-holidays.index', ['embed' => 1]),
            ],
            [
                'key' => 'absensi',
                'label' => 'Absensi Harian',
                'permission' => ['attendance view', 'attendance manage'],
                'src' => route('hr-attendances.index', ['embed' => 1]),
            ],
            [
                'key' => 'acc_lembur',
                'label' => 'ACC Lembur',
                'permission' => 'attendance manage',
                'src' => route('hr-overtime-approvals.index', ['embed' => 1]),
            ],
            [
                'key' => 'live',
                'label' => 'Live Tracking',
                'permission' => ['attendance view', 'attendance manage'],
                'src' => route('hr-attendances-live.index', ['embed' => 1]),
            ],
            [
                'key' => 'operasional',
                'label' => 'Operasional',
                'permission' => 'attendance payroll',
                'src' => route('hr-operational.index', ['embed' => 1]),
            ],
            [
                'key' => 'potongan',
                'label' => 'Potongan',
                'permission' => 'attendance payroll',
                'src' => route('hr-potongan.index', ['embed' => 1]),
            ],
            [
                'key' => 'kasbon',
                'label' => 'Kasbon',
                'permission' => 'attendance payroll',
                'src' => route('hr-kasbons.index', ['embed' => 1]),
            ],
            [
                'key' => 'payroll',
                'label' => 'Payroll',
                'permission' => 'attendance payroll',
                'src' => route('hr-payroll-periods.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'absensi');
        return view('hub.tabs', [
            'title' => 'HR & Absensi',
            'subtitle' => 'Pusat master, absensi, dan payroll.',
            'routeName' => 'hr-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
