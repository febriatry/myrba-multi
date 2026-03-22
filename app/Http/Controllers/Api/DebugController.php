<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function apiKeyCheck(Request $request)
    {
        $receivedHeader = $request->header('X-API-KEY');
        $receivedBody = $request->input('api_key');
        $received = $receivedHeader ?: $receivedBody;
        $envActive = config('myrba.api_key');
        $equals = ($received && $envActive) ? hash_equals($envActive, $received) : false;
        $source = $receivedHeader ? 'header' : ($receivedBody ? 'body' : 'none');
        $envSource = $envActive ? 'config(myrba.api_key)' : 'none';
        return apiResponse(true, 'OK', [
            'received_source' => $source,
            'equals_env' => $equals,
            'env_source' => $envSource,
        ]);
    }
}
