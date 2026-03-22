<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorController extends Controller
{
    public function wallet(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) return $apiKeyError;
        $user = $request->user();
        if (!$user) return apiResponse(false, 'Unauthorized', [], 401);
        $wallet = DB::table('investor_wallets')->where('user_id', $user->id)->first();
        $balance = (float) ($wallet->balance ?? 0);
        return apiResponse(true, 'OK', ['balance' => $balance]);
    }

    public function walletHistory(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) return $apiKeyError;
        $user = $request->user();
        if (!$user) return apiResponse(false, 'Unauthorized', [], 401);
        $limit = intval($request->input('limit', 50));
        $data = DB::table('investor_wallet_histories')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
        return apiResponse(true, 'OK', ['data' => $data]);
    }

    public function createPayoutRequest(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) return $apiKeyError;
        $user = $request->user();
        if (!$user) return apiResponse(false, 'Unauthorized', [], 401);
        $amount = (float) $request->input('amount');
        if ($amount <= 0) return apiResponse(false, 'Amount invalid', [], 422);
        $account = DB::table('investor_payout_accounts')->where('user_id', $user->id)->first();
        if (!$account || empty($account->account_number) || empty($account->account_name) || empty($account->type)) {
            return apiResponse(false, 'Lengkapi rekening/e-wallet terlebih dahulu', [], 422);
        }

        return DB::transaction(function () use ($user, $amount, $account) {
            $wallet = DB::table('investor_wallets')->where('user_id', $user->id)->lockForUpdate()->first();
            $balance = (float) ($wallet->balance ?? 0);
            $pendingAmount = (float) DB::table('investor_payout_requests')
                ->where('user_id', $user->id)
                ->where('status', 'Pending')
                ->lockForUpdate()
                ->sum('amount');
            $available = max(0, $balance - $pendingAmount);
            if ($available < $amount) {
                return apiResponse(false, 'Saldo tidak cukup', ['available' => $available], 422);
            }
            DB::table('investor_payout_requests')->insert([
                'user_id' => $user->id,
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
            return apiResponse(true, 'Request payout dibuat', []);
        });
    }
}
