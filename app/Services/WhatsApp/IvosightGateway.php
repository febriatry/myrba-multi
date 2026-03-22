<?php

namespace App\Services\WhatsApp;

use App\Models\WaTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IvosightGateway
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $senderId;
    protected array $templateEndpoints;
    protected int $timeout;

    public function __construct(?array $overrides = null)
    {
        $overrides = is_array($overrides) ? $overrides : [];
        $this->baseUrl = rtrim(config('whatsapp.ivosight.base_url'), '/');
        $this->apiKey = config('whatsapp.ivosight.api_key');
        $this->senderId = config('whatsapp.ivosight.sender_id');
        $this->templateEndpoints = config('whatsapp.ivosight.template_endpoints', []);
        $this->timeout = (int) config('whatsapp.ivosight.timeout', 30);

        if (isset($overrides['base_url']) && is_string($overrides['base_url']) && trim($overrides['base_url']) !== '') {
            $this->baseUrl = rtrim(trim($overrides['base_url']), '/');
        }
        if (isset($overrides['api_key']) && is_string($overrides['api_key']) && trim($overrides['api_key']) !== '') {
            $this->apiKey = trim($overrides['api_key']);
        }
        if (array_key_exists('sender_id', $overrides)) {
            $this->senderId = is_string($overrides['sender_id']) ? trim($overrides['sender_id']) : '';
        }
    }

    public function sendText(string $to, string $message)
    {
        $endpoint = $this->baseUrl . '/api/v1/messages/send-text-message';
        $payload = [
            'wa_id' => $this->normalizeWaId($to),
            'text' => $message,
        ];
        if (!empty($this->senderId)) {
            $payload['sender_id'] = $this->senderId;
        }
        return $this->httpClient([
            'X-IDEMPOTENCY-KEY' => (string) \Illuminate\Support\Str::uuid(),
        ])->post($endpoint, $payload);
    }

    public function sendTemplate(string $to, string $templateReference, array $components = [])
    {
        $endpoint = $this->baseUrl . '/api/v1/messages/send-template-message';
        $templateMeta = $this->resolveTemplateMeta($templateReference);
        $payload = [
            'wa_id' => $this->normalizeWaId($to),
            'template_id' => $templateMeta['template_id'],
            'components' => $components,
        ];

        if (!empty($this->senderId)) {
            $payload['sender_id'] = $this->senderId;
        }

        if (!empty($templateMeta['language_id'])) {
            $payload['language_id'] = $templateMeta['language_id'];
        }

        Log::info('Ivosight Send Template Payload:', $payload);

        return $this->httpClient([
            'X-IDEMPOTENCY-KEY' => (string) \Illuminate\Support\Str::uuid(),
        ])->post($endpoint, $payload);
    }

    public function fetchTemplates(): array
    {
        if ($this->baseUrl === '' || !str_starts_with($this->baseUrl, 'http')) {
            return [];
        }

        foreach ($this->templateEndpoints as $path) {
            $endpoint = $this->baseUrl . '/' . ltrim($path, '/');
            try {
                $response = $this->httpClient()->get($endpoint);
            } catch (\Throwable $e) {
                Log::warning('Ivosight template fetch failed', [
                    'endpoint' => $endpoint,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }

            if (!$response->successful()) {
                Log::warning('Ivosight template fetch non-success', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
                continue;
            }
            $data = $response->json();
            $templates = $this->extractTemplates($data);
            if (!empty($templates)) {
                return $templates;
            }
        }

        return [];
    }

    public function testConnection(): array
    {
        $report = [
            'base_url' => $this->baseUrl,
            'base_url_valid' => $this->baseUrl !== '' && str_starts_with($this->baseUrl, 'http'),
            'api_key_present' => $this->apiKey !== '',
            'template_endpoints' => [],
            'ok' => false,
        ];

        if (!$report['base_url_valid'] || !$report['api_key_present']) {
            return $report;
        }

        foreach ($this->templateEndpoints as $path) {
            $endpoint = $this->baseUrl . '/' . ltrim($path, '/');
            $item = [
                'endpoint' => $endpoint,
                'status' => null,
                'success' => false,
                'message' => null,
            ];

            try {
                $response = $this->httpClient()->get($endpoint);
                $item['status'] = $response->status();
                $item['success'] = $response->successful();
                if ($response->successful()) {
                    $item['message'] = 'OK';
                    $report['ok'] = true;
                } else {
                    $item['message'] = $response->body();
                }
            } catch (\Throwable $e) {
                $item['message'] = $e->getMessage();
            }

            $report['template_endpoints'][] = $item;
        }

        return $report;
    }

    private function httpClient(array $headers = [])
    {
        return Http::timeout($this->timeout)->withHeaders(array_merge([
            'X-API-KEY' => $this->apiKey,
            'Accept' => 'application/json',
        ], $headers));
    }

    private function extractTemplates($payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $candidateKeys = ['templates', 'data', 'results', 'items'];
        foreach ($candidateKeys as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    private function normalizeWaId(string $to): string
    {
        $clean = preg_replace('/\D+/', '', $to) ?? '';
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        }
        if (str_starts_with($clean, '62')) {
            return $clean;
        }
        return $clean;
    }

    private function resolveTemplateMeta(string $templateReference): array
    {
        $reference = trim((string) $templateReference);
        if ($reference === '') {
            return [
                'template_id' => $reference,
                'language_id' => null,
            ];
        }
        $normalized = strtolower($reference);
        $template = WaTemplate::query()
            ->whereRaw('LOWER(template_id) = ?', [$normalized])
            ->orWhereRaw('LOWER(name) = ?', [$normalized])
            ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, "$.template_name"))) = ?', [$normalized])
            ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, "$.name"))) = ?', [$normalized])
            ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, "$.label"))) = ?', [$normalized])
            ->first();

        if ($template) {
            return [
                'template_id' => (string) $template->template_id,
                'language_id' => $this->normalizeLanguageId($template->language ?? null),
            ];
        }

        return [
            'template_id' => $reference,
            'language_id' => null,
        ];
    }

    private function normalizeLanguageId($language): ?string
    {
        $value = trim((string) $language);
        return $value === '' ? null : $value;
    }

}
