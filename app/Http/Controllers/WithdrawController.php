<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use App\Http\Requests\{StoreWithdrawRequest, UpdateWithdrawRequest};
use App\Models\BalanceHistory;
use App\Models\Pelanggan;
use App\Models\CategoryPengeluaran;
use App\Models\Pengeluaran;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:withdraw view')->only('index', 'show');
        $this->middleware('permission:withdraw create')->only('create', 'store');
        $this->middleware('permission:withdraw edit')->only('edit', 'update');
        $this->middleware('permission:withdraw delete')->only('destroy');
        $this->middleware('permission:withdraw approval')->only('approve');
    }

    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if (request()->ajax()) {
            $allowedAreas = getAllowedAreaCoverageIdsForUser();
            $withdraws = DB::table('withdraws')
                ->leftJoin('pelanggans', 'withdraws.pelanggan_id', '=', 'pelanggans.id')
                ->leftJoin('users', 'withdraws.user_approved', '=', 'users.id')
                ->select(
                    'withdraws.*',
                    'pelanggans.nama as pelanggan_nama',
                    'pelanggans.no_layanan as pelanggan_no_layanan',
                    'users.name as approver_name'
                )
                ->where('withdraws.tenant_id', $tenantId)
                ->where('pelanggans.tenant_id', $tenantId)
                ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                    $q->whereIn('pelanggans.coverage_area', $allowedAreas);
                })
                ->orderBy('withdraws.updated_at', 'desc');

            return DataTables::of($withdraws)
                ->addIndexColumn()
                ->addColumn('pelanggan', function ($row) {
                    return $row->pelanggan_nama
                        ? $row->pelanggan_nama . ' (' . $row->pelanggan_no_layanan . ')'
                        : '-';
                })
                ->addColumn('nominal_wd', fn($row) => rupiah($row->nominal_wd))
                ->addColumn('status', function ($row) {
                    $colors = ['Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger'];
                    return '<span class="badge bg-' . ($colors[$row->status] ?? 'secondary') . '">' . $row->status . '</span>';
                })
                ->addColumn('user_approved', fn($row) => $row->approver_name ?? '-')
                ->addColumn('action', 'withdraws.include.action')
                ->rawColumns(['status', 'action'])
                ->toJson();
        }

        return view('withdraws.index');
    }

    public function create()
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $pelanggans = Pelanggan::where('balance', '>', 0)
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('coverage_area', $allowedAreas);
            })
            ->get(['id', 'nama', 'no_layanan', 'balance']);
        return view('withdraws.create', compact('pelanggans'));
    }

    public function store(StoreWithdrawRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $pelangganOk = Pelanggan::where('id', (int) $request->pelanggan_id)->exists();
        if (!$pelangganOk) {
            abort(404);
        }
        $withdraw = Withdraw::create(array_merge(
            $request->validated(),
            [
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ));
        createTiketAduanForWithdraw((int) $withdraw->id);

        return redirect()
            ->route('withdraws.index')
            ->with('success', __('Permintaan withdraw berhasil dibuat.'));
    }

    public function show(Withdraw $withdraw)
    {
        $withdraw->load('pelanggan', 'approver');
        return view('withdraws.show', compact('withdraw'));
    }

    public function edit(Withdraw $withdraw)
    {
        // Hanya user dengan balance > 0 yang bisa dipilih
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $pelanggans = Pelanggan::where('balance', '>', 0)
            ->orWhere('id', $withdraw->pelanggan_id)
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('coverage_area', $allowedAreas);
            })
            ->get(['id', 'nama', 'no_layanan', 'balance']);
        return view('withdraws.edit', compact('withdraw', 'pelanggans'));
    }

    public function update(UpdateWithdrawRequest $request, Withdraw $withdraw)
    {
        // Hanya bisa edit jika status masih 'Pending'
        if ($withdraw->status != 'Pending') {
            return redirect()->route('withdraws.index')->with('error', 'Data yang sudah diproses tidak dapat diubah.');
        }

        $withdraw->update($request->validated());

        return redirect()
            ->route('withdraws.index')
            ->with('success', __('Permintaan withdraw berhasil diubah.'));
    }

    public function destroy(Withdraw $withdraw)
    {
        // Hanya bisa hapus jika status masih 'Pending'
        if ($withdraw->status != 'Pending') {
            return redirect()->route('withdraws.index')->with('error', 'Data yang sudah diproses tidak dapat dihapus.');
        }

        try {
            $withdraw->delete();
            return redirect()
                ->route('withdraws.index')
                ->with('success', __('Permintaan withdraw berhasil dihapus.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('withdraws.index')
                ->with('error', __("Data tidak bisa dihapus karena terkait dengan tabel lain."));
        }
    }

    public function approve(Request $request, Withdraw $withdraw)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $request->validate([
            'catatan' => 'nullable|string|max:255',
            'action' => 'required|in:approve,reject'
        ]);

        if ($withdraw->status != 'Pending') {
            return redirect()->back()->with('error', 'Withdrawal ini sudah diproses.');
        }

        $pelanggan = Pelanggan::find($withdraw->pelanggan_id);
        $catatan = $request->input('catatan');
        if (empty($catatan)) {
            $statusText = $request->action == 'approve' ? 'Disetujui' : 'Ditolak';
            $catatan = "Proses approval " . $statusText . " oleh " . Auth::user()->name;
        }

        if ($request->action == 'approve') {
            autoPayTagihanWithSaldo($withdraw->pelanggan_id);
            $pelanggan = Pelanggan::find($withdraw->pelanggan_id);
            if ($pelanggan->balance < $withdraw->nominal_wd) {
                return redirect()->back()->with('error', 'Saldo pelanggan tidak mencukupi untuk approval.');
            }

            DB::beginTransaction();
            try {
                // 1. Setujui permintaan saat ini
                $withdraw->update([
                    'status' => 'Approved',
                    'user_approved' => Auth::id(),
                    'catatan_user_approved' => $catatan,
                    'updated_at' => now(),
                ]);

                $balanceBefore = $pelanggan->balance;
                $balanceAfter = $pelanggan->balance - $withdraw->nominal_wd;

                // 2. Kurangi saldo pelanggan
                $pelanggan->update(['balance' => $balanceAfter]);

                $catatanPengeluaran = "Withdraw sebesar " . rupiah($withdraw->nominal_wd) . " pelanggan " . $pelanggan->nama . " pada tanggal " . $withdraw->tanggal_wd->format('d-m-Y');

                $kategoriWithdraw = CategoryPengeluaran::firstOrCreate([
                    'tenant_id' => $tenantId,
                    'nama_kategori_pengeluaran' => 'Withdraw Saldo Pelanggan',
                ]);

                // 3. Masukkan ke tabel pengeluaran
                Pengeluaran::create([
                    'tenant_id' => $tenantId,
                    'nominal' => $withdraw->nominal_wd,
                    'tanggal' => now(),
                    'keterangan' => $catatanPengeluaran,
                    'category_pengeluaran_id' => $kategoriWithdraw->id,
                ]);

                // 4. Masukkan ke history balance
                BalanceHistory::create([
                    'tenant_id' => $tenantId,
                    'pelanggan_id' => $pelanggan->id,
                    'type' => 'Pengurangan',
                    'amount' => $withdraw->nominal_wd,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => "Withdraw sebesar " . rupiah($withdraw->nominal_wd) . " pada tanggal " . $withdraw->tanggal_wd->format('d-m-Y'),
                ]);

                // 5. LOGIKA BARU: Cek dan tolak otomatis permintaan lain yang tidak valid
                Withdraw::where('pelanggan_id', $pelanggan->id)
                    ->where('status', 'Pending')
                    ->where('nominal_wd', '>', $balanceAfter) // Jika nominal WD > sisa saldo
                    ->update([
                        'status' => 'Rejected',
                        'user_approved' => Auth::id(),
                        'catatan_user_approved' => 'Ditolak otomatis oleh sistem karena saldo tidak mencukupi setelah approval sebelumnya.',
                    ]);

                DB::commit();
                return redirect()->route('withdraws.index')->with('success', 'Withdrawal berhasil disetujui. Permintaan lain yang tidak valid telah ditolak otomatis.');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        } else {

            $withdraw->update([
                'status' => 'Rejected',
                'user_approved' => Auth::id(),
                'catatan_user_approved' => $catatan,
            ]);

            return redirect()->route('withdraws.index')->with('success', 'Withdrawal berhasil ditolak.');
        }
    }
}
