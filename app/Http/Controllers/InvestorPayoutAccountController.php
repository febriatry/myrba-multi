<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestorPayoutAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor payout request']);
    }

    public function edit()
    {
        $userId = (int) Auth::id();
        $account = DB::table('investor_payout_accounts')->where('user_id', $userId)->first();
        return view('investor-payout-account.edit', compact('account'));
    }

    public function update(Request $request)
    {
        $userId = (int) Auth::id();
        $validated = $request->validate([
            'type' => 'required|in:bank,ewallet',
            'provider' => 'nullable|string|max:50',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:100',
        ]);

        $payload = [
            'type' => $validated['type'],
            'provider' => !empty($validated['provider']) ? trim((string) $validated['provider']) : null,
            'account_name' => trim((string) $validated['account_name']),
            'account_number' => trim((string) $validated['account_number']),
            'updated_at' => now(),
        ];
        $exists = DB::table('investor_payout_accounts')->where('user_id', $userId)->exists();
        if ($exists) {
            DB::table('investor_payout_accounts')->where('user_id', $userId)->update($payload);
        } else {
            $payload['user_id'] = $userId;
            $payload['created_at'] = now();
            DB::table('investor_payout_accounts')->insert($payload);
        }

        return redirect()->route('investor-payout-account.index')->with('success', 'Data rekening/e-wallet berhasil disimpan.');
    }
}
