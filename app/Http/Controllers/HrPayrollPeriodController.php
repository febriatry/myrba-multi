<?php

namespace App\Http\Controllers;

use App\Services\HrPayrollGenerator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HrPayrollPeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance payroll']);
    }

    public function index(Request $request)
    {
        $rows = DB::table('hr_payroll_periods')
            ->orderByDesc('period_start')
            ->paginate(20)
            ->withQueryString();

        return view('hr-payroll-periods.index', compact('rows'));
    }

    public function create()
    {
        return view('hr-payroll-periods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|string|max:7',
        ]);

        $month = trim((string) $validated['month']);
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $label = $month;

        $exists = DB::table('hr_payroll_periods')->where('period_start', $start)->where('period_end', $end)->exists();
        if ($exists) {
            return back()->withErrors(['month' => 'Periode payroll sudah ada.'])->withInput();
        }

        $id = (int) DB::table('hr_payroll_periods')->insertGetId([
            'period_start' => $start,
            'period_end' => $end,
            'label' => $label,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-payroll-periods.show', $id)->with('success', 'Periode payroll berhasil dibuat.');
    }

    public function show(int $id)
    {
        $row = DB::table('hr_payroll_periods')->where('id', $id)->first();
        abort_if(!$row, 404);

        $items = DB::table('hr_payroll_items as pi')
            ->join('users as u', 'pi.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 'pi.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('pi.period_id', $id)
            ->select('pi.*', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name')
            ->orderBy('u.name')
            ->get();

        $summary = [
            'employees' => (int) $items->count(),
            'present_days' => (int) $items->sum('present_days'),
            'base_total' => (int) $items->sum('base_amount'),
            'overtime_total' => (int) $items->sum('overtime_amount'),
            'operational_total' => (int) $items->sum('operational_amount'),
            'mandatory_total' => (int) $items->sum('mandatory_deduction_amount'),
            'sanction_total' => (int) $items->sum('sanction_deduction_amount'),
            'other_deduction_total' => Schema::hasColumn('hr_payroll_items', 'other_deduction_amount') ? (int) $items->sum('other_deduction_amount') : 0,
            'kasbon_deduction_total' => Schema::hasColumn('hr_payroll_items', 'kasbon_deduction_amount') ? (int) $items->sum('kasbon_deduction_amount') : 0,
            'grand_total' => (int) $items->sum('total_amount'),
        ];

        return view('hr-payroll-periods.show', compact('row', 'items', 'summary'));
    }

    public function generate(int $id)
    {
        $result = HrPayrollGenerator::generate($id);
        if (!($result['ok'] ?? false)) {
            return redirect()->route('hr-payroll-periods.show', $id)->withErrors(['payroll' => $result['message'] ?? 'Gagal generate payroll.']);
        }
        return redirect()->route('hr-payroll-periods.show', $id)->with('success', 'Payroll berhasil digenerate.');
    }

    public function lock(int $id)
    {
        $row = DB::table('hr_payroll_periods')->where('id', $id)->first();
        abort_if(!$row, 404);

        DB::table('hr_payroll_periods')->where('id', $id)->update([
            'status' => 'locked',
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-payroll-periods.show', $id)->with('success', 'Periode payroll berhasil dikunci.');
    }

    public function postToFinance(int $id)
    {
        $row = DB::table('hr_payroll_periods')->where('id', $id)->first();
        abort_if(!$row, 404);
        if (!empty($row->finance_pengeluaran_id)) {
            return redirect()->route('hr-payroll-periods.show', $id)->withErrors(['payroll' => 'Payroll sudah diposting ke keuangan.']);
        }

        $total = (int) DB::table('hr_payroll_items')->where('period_id', $id)->sum('total_amount');

        $cat = DB::table('category_pengeluarans')->where('nama_kategori_pengeluaran', 'Gaji Karyawan')->first();
        if (!$cat) {
            $catId = (int) DB::table('category_pengeluarans')->insertGetId([
                'nama_kategori_pengeluaran' => 'Gaji Karyawan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $catId = (int) $cat->id;
        }

        $pengeluaranId = (int) DB::table('pengeluarans')->insertGetId([
            'nominal' => $total,
            'tanggal' => now(),
            'keterangan' => 'Payroll ' . (string) $row->label . ' (period_id=' . (int) $id . ')',
            'category_pengeluaran_id' => $catId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hr_payroll_periods')->where('id', $id)->update([
            'finance_pengeluaran_id' => $pengeluaranId,
            'posted_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-payroll-periods.show', $id)->with('success', 'Payroll berhasil diposting ke laporan keuangan.');
    }

    public function exportPdf(int $id)
    {
        $row = DB::table('hr_payroll_periods')->where('id', $id)->first();
        abort_if(!$row, 404);

        $items = DB::table('hr_payroll_items as pi')
            ->join('users as u', 'pi.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 'pi.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('pi.period_id', $id)
            ->select('pi.*', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name')
            ->orderBy('u.name')
            ->get();

        $summary = [
            'employees' => (int) $items->count(),
            'present_days' => (int) $items->sum('present_days'),
            'base_total' => (int) $items->sum('base_amount'),
            'overtime_total' => (int) $items->sum('overtime_amount'),
            'operational_total' => (int) $items->sum('operational_amount'),
            'mandatory_total' => (int) $items->sum('mandatory_deduction_amount'),
            'sanction_total' => (int) $items->sum('sanction_deduction_amount'),
            'other_deduction_total' => Schema::hasColumn('hr_payroll_items', 'other_deduction_amount') ? (int) $items->sum('other_deduction_amount') : 0,
            'kasbon_deduction_total' => Schema::hasColumn('hr_payroll_items', 'kasbon_deduction_amount') ? (int) $items->sum('kasbon_deduction_amount') : 0,
            'grand_total' => (int) $items->sum('total_amount'),
        ];

        $pdf = Pdf::loadView('hr-payroll-periods.export-pdf', [
            'title' => 'Payroll ' . (string) $row->label,
            'row' => $row,
            'items' => $items,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('payroll_' . (string) $row->label . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
