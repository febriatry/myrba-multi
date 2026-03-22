<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY') ?: $request->input('api_key');
        $validApiKeys = (array) config('myrba.api_keys', []);
        if (empty($validApiKeys)) {
            $fallback = (string) config('myrba.api_key');
            if ($fallback !== '') {
                $validApiKeys = [$fallback];
            }
        }

        $isValid = false;
        if (!empty($apiKey) && !empty($validApiKeys)) {
            foreach ($validApiKeys as $valid) {
                $valid = (string) $valid;
                if ($valid !== '' && hash_equals($valid, (string) $apiKey)) {
                    $isValid = true;
                    break;
                }
            }
        }

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid API Key',
            ], 401);
        }

        return $next($request);
    }
}
