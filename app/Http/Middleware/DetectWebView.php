<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DetectWebView
{
    public function handle(Request $request, Closure $next)
    {
        $ua = $request->header('User-Agent', '');
        $isAndroidWebView = false;
        if (stripos($ua, 'Android') !== false && (stripos($ua, 'wv') !== false || stripos($ua, 'Version/') !== false)) {
            $isAndroidWebView = true;
        }
        if ($request->query('app') === '1') {
            $isAndroidWebView = true;
        }
        View::share('isWebViewApp', $isAndroidWebView);
        return $next($request);
    }
}
