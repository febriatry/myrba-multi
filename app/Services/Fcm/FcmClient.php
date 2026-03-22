<?php

namespace App\Services\Fcm;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class FcmClient
{
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        return $this->send([
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->normalizeData($data),
        ]);
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        return $this->send([
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->normalizeData($data),
        ]);
    }

    private function send(array $message): array
    {
        $projectId = (string) config('fcm.project_id');
        $accessToken = $this->getAccessToken();

        $http = new Client([
            'timeout' => 15,
        ]);

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $res = $http->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'message' => $message,
            ],
        ]);

        return json_decode((string) $res->getBody(), true) ?: [];
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'fcm_access_token_v1';
        $token = Cache::get($cacheKey);
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $saPath = (string) config('fcm.service_account_path');
        if ($saPath === '' || !is_file($saPath)) {
            throw new \RuntimeException('FCM service account file tidak ditemukan.');
        }

        $json = json_decode((string) file_get_contents($saPath), true);
        if (!is_array($json)) {
            throw new \RuntimeException('FCM service account file tidak valid.');
        }

        $clientEmail = (string) ($json['client_email'] ?? '');
        $privateKey = (string) ($json['private_key'] ?? '');
        if ($clientEmail === '' || $privateKey === '') {
            throw new \RuntimeException('FCM service account missing client_email/private_key.');
        }

        $now = time();
        $jwt = $this->buildJwt(
            privateKey: $privateKey,
            claims: [
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => self::OAUTH_TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ]
        );

        $http = new Client([
            'timeout' => 15,
        ]);

        $res = $http->post(self::OAUTH_TOKEN_URL, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ]);

        $payload = json_decode((string) $res->getBody(), true);
        $accessToken = (string) ($payload['access_token'] ?? '');
        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        if ($accessToken === '') {
            throw new \RuntimeException('Gagal mengambil FCM access token.');
        }

        Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 120));
        return $accessToken;
    }

    private function buildJwt(string $privateKey, array $claims): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode($claims));
        $signingInput = $header . '.' . $payload;

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new \RuntimeException('Gagal sign JWT untuk FCM.');
        }

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function normalizeData(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v === null) {
                continue;
            }
            $out[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }
        return $out;
    }
}

