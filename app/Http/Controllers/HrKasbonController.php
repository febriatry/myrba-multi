<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrKasbonController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:attendance payroll']);
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table('hr_kasbons as k')
            ->join('users as u', 'k.user_id', '=', 'u.id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('u.name', 'like', '%' . $q . '%')
                        ->orWhere('u.email', 'like', '%' . $q . '%')
                        ->orWhere('k.note', 'like', '%' . $q . '%');
                });
            })
            ->when($date !== '', function ($query) use ($date) {
                $query->where('k.date', $date);
            })
            ->select('k.*', 'u.name as user_name', 'u.email as user_email')
            ->orderByDesc('k.id')
            ->paginate(20)
            ->withQueryString();

        $employees = DB::table('hr_employee_profiles as ep')
            ->join('users as u', 'ep.user_id', '=', 'u.id')
            ->where('ep.is_active', 'Yes')
            ->select('u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        return view('hr-kasbons.index', compact('rows', 'employees', 'date', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date' => 'required|date',
            'amount' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $cat = DB::table('category_pengeluarans')->where('nama_kategori_pengeluaran', 'Kasbon Karyawan')->first();
        if (!$cat) {
            $catId = (int) DB::table('category_pengeluarans')->insertGetId([
                'nama_kategori_pengeluaran' => 'Kasbon Karyawan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $catId = (int) $cat->id;
        }

        $pengeluaranId = (int) DB::table('pengeluarans')->insertGetId([
            'nominal' => (int) $validated['amount'],
            'tanggal' => (string) $validated['date'] . ' 00:00:00',
            'keterangan' => 'Kasbon karyawan user_id=' . (int) $validated['user_id'] . ' | ' . (string) ($validated['note'] ?? ''),
            'category_pengeluaran_id' => $catId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) DB::table('hr_kasbons')->insertGetId([
            'user_id' => (int) $validated['user_id'],
            'date' => (string) $validated['date'],
            'amount' => (int) $validated['amount'],
            'remaining_amount' => (int) $validated['amount'],
            'status' => 'open',
            'note' => !empty($validated['note']) ? trim((string) $validated['note']) : null,
            'finance_pengeluaran_id' => $pengeluaranId,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-kasbons.show', $id)->with('success', 'Kasbon berhasil dibuat.');
    }

    public function show(int $id)
    {
        $row = DB::table('hr_kasbons as k')
            ->join('users as u', 'k.user_id', '=', 'u.id')
            ->where('k.id', $id)
            ->select('k.*', 'u.name as user_name', 'u.email as user_email')
            ->first();
        abort_if(!$row, 404);

        $repayments = DB::table('hr_kasbon_repayments')
            ->where('kasbon_id', $id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('hr-kasbons.show', compact('row', 'repayments'));
    }

    public function addRepayment(Request $request, int $id)
    {
        $kasbon = DB::table('hr_kasbons')->where('id', $id)->first();
        abort_if(!$kasbon, 404);

        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|integer|min:0',
            'source' => 'required|in:payroll,cash,transfer',
            'note' => 'nullable|string|max:255',
        ]);

        $financePemasukanId = null;
        if ((string) $validated['source'] !== 'payroll') {
            $cat = DB::table('category_pemasukans')->where('nama_kategori_pemasukan', 'Pengembalian Kasbon')->first();
            if (!$cat) {
                $catId = (int) DB::table('category_pemasukans')->insertGetId([
                    'nama_kategori_pemasukan' => 'Pengembalian Kasbon',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $catId = (int) $cat->id;
            }

            $financePemasukanId = (int) DB::table('pemasukans')->insertGetId([
                'nominal' => (int) $validated['amount'],
                'tanggal' => (string) $validated['date'] . ' 00:00:00',
                'keterangan' => 'Pengembalian kasbon kasbon_id=' . (int) $id . ' user_id=' . (int) $kasbon->user_id . ' | ' . (string) ($validated['note'] ?? ''),
                'category_pemasukan_id' => $catId,
                'referense_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('hr_kasbon_repayments')->insert([
            'kasbon_id' => $id,
            'user_id' => (int) $kasbon->user_id,
            'date' => (string) $validated['date'],
            'amount' => (int) $validated['amount'],
            'source' => (string) $validated['source'],
            'note' => !empty($validated['note']) ? trim((string) $validated['note']) : null,
            'finance_pemasukan_id' => $financePemasukanId,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $remaining = max(0, (int) $kasbon->remaining_amount - (int) $validated['amount']);
        DB::table('hr_kasbons')->where('id', $id)->update([
            'remaining_amount' => $remaining,
            'status' => $remaining <= 0 ? 'closed' : 'open',
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-kasbons.show', $id)->with('success', 'Pembayaran kasbon berhasil ditambahkan.');
    }

    public function destroy(int $id)
    {
        $row = DB::table('hr_kasbons')->where('id', $id)->first();
        abort_if(!$row, 404);

        DB::table('hr_kasbon_repayments')->where('kasbon_id', $id)->delete();
        DB::table('hr_kasbons')->where('id', $id)->delete();

        return redirect()->route('hr-kasbons.index')->with('success', 'Kasbon berhasil dihapus.');
    }
}

