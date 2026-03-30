<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pemasukan view|pengeluaran view|tagihan view|audit keuangan view|laporan view|category pemasukan view|category pengeluaran view|bank account view|bank view|setor view|setor create|setor approve|setor export pdf']);
    }

    public function index(Request $request)
    {
        $enabledFrom = null;
        try {
            $settingWeb = getSettingWeb();
            $enabledFromRaw = $settingWeb?->setor_enabled_from ?? '2026-04-01 00:00:00';
            $enabledFrom = Carbon::parse((string) $enabledFromRaw);
        } catch (\Throwable $e) {
            $enabledFrom = Carbon::parse('2026-04-01 00:00:00');
        }
        $setorEnabled = now()->greaterThanOrEqualTo($enabledFrom);

        $tabs = [
            [
                'key' => 'tagihan',
                'label' => 'Tagihan',
                'permission' => 'tagihan view',
                'src' => route('tagihans.index', ['embed' => 1]),
            ],
            [
                'key' => 'pemasukan',
                'label' => 'Pemasukan',
                'permission' => ['pemasukan view', 'category pemasukan view'],
                'src' => route('finance-income.index', ['embed' => 1]),
            ],
            [
                'key' => 'pengeluaran',
                'label' => 'Pengeluaran',
                'permission' => ['pengeluaran view', 'category pengeluaran view'],
                'src' => route('finance-expense.index', ['embed' => 1]),
            ],
            ...($setorEnabled
                ? [[
                    'key' => 'setor',
                    'label' => 'Setor',
                    'permission' => ['setor view', 'setor create', 'setor approve', 'setor export pdf'],
                    'src' => route('finance-setors.index', ['embed' => 1]),
                ]]
                : []),
            [
                'key' => 'bank',
                'label' => 'Bank',
                'permission' => ['bank view', 'bank account view'],
                'src' => route('finance-bank.index', ['embed' => 1]),
            ],
            [
                'key' => 'laporan',
                'label' => 'Laporan',
                'permission' => ['laporan view', 'audit keuangan view'],
                'src' => route('finance-report.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'tagihan');

        return view('hub.tabs', [
            'title' => 'Keuangan',
            'subtitle' => 'Pusat transaksi dan laporan keuangan.',
            'routeName' => 'finance-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
