<?php

if (!function_exists('apiResponse')) {
    function apiResponse($success, $message = '', $data = [], $statusCode = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}

if (!function_exists('validateApiKey')) {
    function validateApiKey($request)
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
            return apiResponse(false, 'Unauthorized: Invalid API Key', [], 401);
        }

        return null;
    }
}
