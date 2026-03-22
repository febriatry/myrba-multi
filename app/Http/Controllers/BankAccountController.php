<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Http\Requests\{StoreBankAccountRequest, UpdateBankAccountRequest};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:bank account view')->only('index', 'show');
        $this->middleware('permission:bank account create')->only('create', 'store');
        $this->middleware('permission:bank account edit')->only('edit', 'update');
        $this->middleware('permission:bank account delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if (request()->ajax()) {
            $bankAccounts = DB::table('bank_accounts')
                ->leftJoin('banks', 'bank_accounts.bank_id', '=', 'banks.id')
                ->select('bank_accounts.*', 'banks.nama_bank')
                ->where('bank_accounts.tenant_id', $tenantId)
                ->where('banks.tenant_id', $tenantId)
                ->get();
            return DataTables::of($bankAccounts)
                ->addIndexColumn()
                ->addColumn('bank', function ($row) {
                    return $row->nama_bank;
                })->addColumn('action', 'bank-accounts.include.action')
                ->toJson();
        }

        return view('bank-accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('bank-accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBankAccountRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $attr = $request->validated();
        $bankOk = DB::table('banks')->where('id', (int) $attr['bank_id'])->where('tenant_id', $tenantId)->exists();
        if (!$bankOk) {
            abort(404);
        }
        $attr['tenant_id'] = $tenantId;
        BankAccount::create($attr);
        return redirect()
            ->route('bank-accounts.index')
            ->with('success', __('The bankAccount was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load('bank:id,id');

        return view('bank-accounts.show', compact('bankAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(BankAccount $bankAccount)
    {
        $bankAccount->load('bank:id,id');

        return view('bank-accounts.edit', compact('bankAccount'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount)
    {

        $bankAccount->update($request->validated());

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', __('The bankAccount was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankAccount $bankAccount)
    {
        try {
            $bankAccount->delete();

            return redirect()
                ->route('bank-accounts.index')
                ->with('success', __('The bankAccount was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('bank-accounts.index')
                ->with('error', __("The bankAccount can't be deleted because it's related to another table."));
        }
    }
}
