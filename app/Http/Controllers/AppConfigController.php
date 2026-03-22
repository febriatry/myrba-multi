<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppConfigController extends Controller
{
    public function index(Request $request)
    {
        $setting = DB::table('setting_web')->first();
        return response()->json([
            'app_name' => 'Myrba Admin',
            'company_name' => $setting->nama_perusahaan ?? 'Myrba',
            'logo_url' => $setting && $setting->logo ? asset('storage/uploads/logos/' . $setting->logo) : null,
            'base_url' => url('/'),
            'login_url' => route('login'),
            'invoice_url_pattern' => url('/invoice/{id}'),
            'version' => config('app.version', '1.0'),
            'is_webview_app' => view()->shared('isWebViewApp') ?? false,
        ]);
    }
}
