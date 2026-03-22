<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestorPayoutRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor payout request']);
    }

    public function index()
    {
        $userId = (int) Auth::id();
        $wallet = DB::table('investor_wallets')->where('user_id', $userId)->first();
        $balance = (float) ($wallet->balance ?? 0);
        $pendingAmount = (float) (DB::table('investor_payout_requests')->where('user_id', $userId)->where('status', 'Pending')->sum('amount') ?? 0);
        $available = max(0, $balance - $pendingAmount);
        $account = DB::table('investor_payout_accounts')->where('user_id', $userId)->first();
        $requests = DB::table('investor_payout_requests')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();
        return view('investor-payouts.index', compact('balance', 'pendingAmount', 'available', 'account', 'requests'));
    }

    public function store(Request $request)
    {
        $userId = (int) Auth::id();
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
        $amount = (float) $validated['amount'];

        $account = DB::table('investor_payout_accounts')->where('user_id', $userId)->first();
        if (!$account || empty($account->account_number) || empty($account->account_name) || empty($account->type)) {
            return redirect()->route('investor-payout-account.index')->with('error', 'Lengkapi rekening/e-wallet terlebih dahulu.');
        }

        DB::beginTransaction();
        try {
            $wallet = DB::table('investor_wallets')->where('user_id', $userId)->lockForUpdate()->first();
            $balance = (float) ($wallet->balance ?? 0);
            $pendingAmount = (float) DB::table('investor_payout_requests')
                ->where('user_id', $userId)
                ->where('status', 'Pending')
                ->lockForUpdate()
                ->sum('amount');
            $available = max(0, $balance - $pendingAmount);
            if ($amount > $available) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Saldo tidak cukup. Saldo tersedia: ' . number_format($available, 0, ',', '.') . '.');
            }

            DB::table('investor_payout_requests')->insert([
                'user_id' => $userId,
                'payout_account_id' => (int) $account->id,
                'payout_type' => $account->type,
                'payout_provider' => $account->provider,
                'payout_account_name' => $account->account_name,
                'payout_account_number' => $account->account_number,
                'amount' => $amount,
                'status' => 'Pending',
                'requested_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return redirect()->route('investor-payouts.index')->with('success', 'Request payout berhasil dibuat.');
    }
}
