<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestorPayoutApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor payout approve']);
    }

    public function index()
    {
        $requests = DB::table('investor_payout_requests')
            ->leftJoin('users', 'investor_payout_requests.user_id', '=', 'users.id')
            ->select('investor_payout_requests.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByRaw("CASE WHEN investor_payout_requests.status = 'Pending' THEN 0 ELSE 1 END")
            ->orderByDesc('investor_payout_requests.id')
            ->get();
        return view('investor-payout-requests.index', compact('requests'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $row = DB::table('investor_payout_requests')->where('id', (int) $id)->first();
        if (!$row) {
            return redirect()->back()->with('error', 'Request tidak ditemukan.');
        }
        if ((string) $row->status !== 'Pending') {
            return redirect()->back()->with('error', 'Request sudah diproses.');
        }

        if ($request->input('action') === 'reject') {
            DB::table('investor_payout_requests')->where('id', (int) $id)->update([
                'status' => 'Rejected',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'updated_at' => now(),
            ]);
            return redirect()->route('investor-payout-requests.index')->with('success', 'Request berhasil ditolak.');
        }

        DB::beginTransaction();
        try {
            $locked = DB::table('investor_payout_requests')->where('id', (int) $id)->lockForUpdate()->first();
            if (!$locked || (string) $locked->status !== 'Pending') {
                DB::rollBack();
                return redirect()->back()->with('error', 'Request sudah diproses.');
            }
            $wallet = DB::table('investor_wallets')->where('user_id', (int) $locked->user_id)->lockForUpdate()->first();
            $before = (float) ($wallet->balance ?? 0);
            $amount = (float) ($locked->amount ?? 0);
            if ($amount <= 0 || $before < $amount) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Saldo investor tidak mencukupi.');
            }
            $after = $before - $amount;
            DB::table('investor_wallets')->where('user_id', (int) $locked->user_id)->update([
                'balance' => $after,
                'updated_at' => now(),
            ]);
            DB::table('investor_wallet_histories')->insert([
                'user_id' => (int) $locked->user_id,
                'type' => 'Debit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => 'Payout request #' . (string) $locked->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $category = DB::table('category_pengeluarans')
                ->where('nama_kategori_pengeluaran', 'Mitra/Investor')
                ->first();
            if (!$category) {
                $catId = DB::table('category_pengeluarans')->insertGetId([
                    'nama_kategori_pengeluaran' => 'Mitra/Investor',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $catId = (int) $category->id;
            }
            $user = DB::table('users')->where('id', (int) $locked->user_id)->first();
            $nominalInt = (int) round($amount);
            $payoutTo = '';
            if (!empty($locked->payout_account_number)) {
                $payoutTo = ' - ' . strtoupper((string) ($locked->payout_type ?? '-'));
                if (!empty($locked->payout_provider)) {
                    $payoutTo .= ' ' . (string) $locked->payout_provider;
                }
                $payoutTo .= ' ' . (string) $locked->payout_account_number;
                if (!empty($locked->payout_account_name)) {
                    $payoutTo .= ' a/n ' . (string) $locked->payout_account_name;
                }
            }
            $pengeluaranId = DB::table('pengeluarans')->insertGetId([
                'nominal' => $nominalInt,
                'tanggal' => now(),
                'keterangan' => 'Payout Mitra/Investor ' . ($user->name ?? '-') . ' (request #' . (string) $locked->id . ')' . $payoutTo,
                'category_pengeluaran_id' => $catId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('investor_payout_requests')->where('id', (int) $id)->update([
                'status' => 'Approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'pengeluaran_id' => $pengeluaranId,
                'updated_at' => now(),
            ]);
            DB::commit();
            return redirect()->route('investor-payout-requests.index')->with('success', 'Request berhasil di-approve dan dicatat sebagai pengeluaran.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
