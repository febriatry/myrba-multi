<?php

use App\Models\AreaCoverage;
use App\Models\BalanceHistory;
use App\Models\CategoryPemasukan;
use App\Models\Pelanggan;
use App\Models\Settingmikrotik;
use App\Models\Tagihan;
use App\Models\WaMessageStatusLog;
use App\Models\WaTemplate;
use App\Models\WaTemplateMapping;
use App\Support\WaMessageTrigger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB as DBFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use RouterOS\Client as RouterOSClient;
use RouterOS\Exceptions\ConnectException;

if (! function_exists('is_active_menu')) {
    function is_active_menu(string|array $route): string
    {
        $activeClass = ' active';

        if (is_string($route)) {
            if (request()->is(substr($route.'*', 1))) {
                return $activeClass;
            }

            if (request()->is(str($route)->slug().'*')) {
                return $activeClass;
            }

            if (request()->segment(2) === str($route)->before('/')) {
                return $activeClass;
            }

            if (request()->segment(3) === str($route)->after('/')) {
                return $activeClass;
            }
        }

        if (is_array($route)) {
            foreach ($route as $value) {
                $actualRoute = str($value)->remove(' view')->plural();

                if (request()->is(substr($actualRoute.'*', 1))) {
                    return $activeClass;
                }

                if (request()->is(str($actualRoute)->slug().'*')) {
                    return $activeClass;
                }

                if (request()->segment(2) === $actualRoute) {
                    return $activeClass;
                }

                if (request()->segment(3) === $actualRoute) {
                    return $activeClass;
                }
            }
        }

        return '';
    }
}

function is_active_submenu(string|array $route): string
{
    $activeClass = ' submenu-open';

    if (is_string($route)) {
        if (request()->is(substr($route.'*', 1))) {
            return $activeClass;
        }

        if (request()->is(str($route)->slug().'*')) {
            return $activeClass;
        }

        if (request()->segment(2) === str($route)->before('/')) {
            return $activeClass;
        }

        if (request()->segment(3) === str($route)->after('/')) {
            return $activeClass;
        }
    }

    if (is_array($route)) {
        foreach ($route as $value) {
            $actualRoute = str($value)->remove(' view')->plural();

            if (request()->is(substr($actualRoute.'*', 1))) {
                return $activeClass;
            }

            if (request()->is(str($actualRoute)->slug().'*')) {
                return $activeClass;
            }

            if (request()->segment(2) === $actualRoute) {
                return $activeClass;
            }

            if (request()->segment(3) === $actualRoute) {
                return $activeClass;
            }
        }
    }

    return '';
}

function formatBytes($bytes, $decimal = null)
{
    $satuan = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes > 1024) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, $decimal).' '.$satuan[$i];
}

function setRoute(?int $routerId = null)
{
    $routerId = $routerId ?: (is_numeric(request()->query('router_id')) ? (int) request()->query('router_id') : null);
    $routers = DB::table('settingmikrotiks')
        ->when(! empty($routerId), function ($q) use ($routerId) {
            $q->where('id', $routerId);
        })
        ->orderBy('id')
        ->get();

    foreach ($routers as $router) {
        try {
            return new RouterOSClient([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
            ]);
        } catch (ConnectException $e) {
            continue;
        }
    }

    abort(503, 'Router tidak terhubung.');
}

function myrbaMikrotikCommentTag(): string
{
    return '[MYRBA]';
}

function myrbaBuildMikrotikComment(string $message): string
{
    return myrbaMikrotikCommentTag().' '.trim($message).' @ '.now()->format('Y-m-d H:i:s');
}

function myrbaMergeMikrotikComment(?string $existing, string $message): string
{
    $existing = trim((string) $existing);
    $tag = myrbaMikrotikCommentTag();

    if ($existing !== '') {
        $lines = preg_split('/\\r\\n|\\r|\\n/', $existing) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), function ($line) use ($tag) {
            return $line !== '' && stripos($line, $tag) === false;
        }));
        $existing = implode("\n", $lines);
    }

    $appLine = myrbaBuildMikrotikComment($message);
    if ($existing === '') {
        return $appLine;
    }

    return $existing."\n".$appLine;
}

function setRouteTagihanByPelanggan($router_id)
{
    $router = DB::table('settingmikrotiks')->where('id', $router_id)->first();
    if ($router) {
        try {
            return new RouterOSClient([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
            ]);
        } catch (ConnectException $e) {
            echo $e->getMessage().PHP_EOL;
            exit();
        }
    }
}

function hitungRouter()
{
    return Settingmikrotik::count();
}

function hitungPelanggan()
{
    return Pelanggan::count();
}

function getCustomer()
{
    $data = DB::table('pelanggans')->where('id', Session::get('id-customer'))->first();

    return $data;
}

function rupiah($angka)
{

    $hasil_rupiah = 'Rp '.number_format($angka, 2, ',', '.');

    return $hasil_rupiah;
}

function konversiTanggal($tanggal)
{
    setlocale(LC_TIME, 'id_ID');
    $date = DateTime::createFromFormat('Y-m', $tanggal);
    $tanggal_indonesia = strftime('%B %Y', $date->getTimestamp());

    return $tanggal_indonesia;
}

function sendNotifWa($api_key, $request, $typePesan, $no_penerima)
{
    $tenantId = resolveTenantIdForWaRequest($request);
    if (class_exists(\App\Services\TenantEntitlementService::class)) {
        try {
            if (! \App\Services\TenantEntitlementService::featureEnabled('whatsapp', false)) {
                return (object) [
                    'status' => false,
                    'message' => 'Fitur WhatsApp tidak tersedia untuk tenant ini.',
                    'raw' => null,
                ];
            }
        } catch (\Throwable $e) {
        }
    }
    $tenant = null;
    if ($tenantId > 0 && class_exists(\App\Models\Tenant::class)) {
        $tenant = \App\Models\Tenant::query()->find($tenantId);
    }

    $overrides = null;
    $billingMode = 'owner';
    if ($tenant && strtolower((string) ($tenant->wa_provider_mode ?? 'developer')) === 'tenant') {
        $apiKey = trim((string) ($tenant->wa_ivosight_api_key ?? ''));
        $baseUrl = trim((string) ($tenant->wa_ivosight_base_url ?? ''));
        if ($apiKey !== '' && $baseUrl !== '') {
            $billingMode = 'tenant';
            $overrides = [
                'base_url' => $baseUrl,
                'api_key' => $apiKey,
                'sender_id' => (string) ($tenant->wa_ivosight_sender_id ?? ''),
            ];
        }
    }

    $gw = new \App\Services\WhatsApp\IvosightGateway($overrides);
    $useTemplate = filter_var(config('whatsapp.ivosight.use_template'), FILTER_VALIDATE_BOOLEAN);
    $normalizedTypePesan = WaMessageTrigger::normalize((string) $typePesan);
    $typeCandidates = array_map('strtolower', WaMessageTrigger::candidates((string) $typePesan));
    $mustUseTemplate = in_array($normalizedTypePesan, [
        WaMessageTrigger::BILLING_REMINDER,
        WaMessageTrigger::BILLING_TOTAL,
        WaMessageTrigger::INVOICE_LINK,
        WaMessageTrigger::BROADCAST,
    ], true);

    if ($useTemplate || $mustUseTemplate) {
        $candidateMappings = WaTemplateMapping::query()
            ->where(function ($query) use ($typeCandidates) {
                foreach ($typeCandidates as $candidate) {
                    $query->orWhereRaw('LOWER(TRIM(message_type)) = ?', [$candidate]);
                }
            })
            ->orderByDesc('updated_at')
            ->get();

        if ($candidateMappings->isNotEmpty()) {
            $selectedType = null;
            foreach ($typeCandidates as $candidate) {
                $exists = $candidateMappings->first(function ($row) use ($candidate) {
                    return strtolower(trim((string) $row->message_type)) === $candidate;
                });
                if ($exists) {
                    $selectedType = $candidate;
                    break;
                }
            }
            $selectedType = $selectedType ?? strtolower(trim((string) $candidateMappings->first()->message_type));

            $selectedTemplateId = resolvePreferredTemplateIdForTrigger(
                $normalizedTypePesan,
                $selectedType,
                $candidateMappings
            );

            $templateIds = [];
            if ($selectedTemplateId !== '') {
                $templateIds[] = $selectedTemplateId;
            }
            foreach ($candidateMappings as $row) {
                $id = trim((string) ($row->template_id ?? ''));
                if ($id !== '' && ! in_array($id, $templateIds, true)) {
                    $templateIds[] = $id;
                }
            }

            $lastFailure = null;
            $firstMissingReport = null;
            foreach ($templateIds as $templateId) {
                $templateMappings = WaTemplateMapping::query()
                    ->where('template_id', $templateId)
                    ->where(function ($query) use ($typeCandidates) {
                        foreach ($typeCandidates as $candidate) {
                            $query->orWhereRaw('LOWER(TRIM(message_type)) = ?', [$candidate]);
                        }
                    })
                    ->orderBy('component_type')
                    ->orderBy('component_index')
                    ->orderBy('param_index')
                    ->get();

                $build = buildWaTemplateComponentsReport($templateMappings, $request, $normalizedTypePesan);
                $components = $build['components'];
                if (empty($components)) {
                    if ($firstMissingReport === null) {
                        $firstMissingReport = [
                            'template_id' => $templateId,
                            'missing_required' => $build['missing_required'] ?? [],
                        ];
                    }

                    continue;
                }

                $templateRes = $gw->sendTemplate(strval($no_penerima), $templateId, $components);
                $parsedTemplateRes = parseWaGatewayResponse($templateRes, 'Gagal mengirim template WA');
                if ($parsedTemplateRes->status === true || $parsedTemplateRes->status === 'true') {
                    myrbaRecordWaSentLogIfMissing($parsedTemplateRes->message_id ?? null, $normalizedTypePesan, $no_penerima, $parsedTemplateRes->raw ?? null, $tenantId, 'ivosight', $billingMode);

                    return $parsedTemplateRes;
                }

                $message = (string) ($parsedTemplateRes->message ?? '');
                $lastFailure = $parsedTemplateRes;

                $waTemplate = WaTemplate::query()->where('template_id', $templateId)->first();
                $templateNameForGateway = resolveWaTemplateReferenceName($waTemplate);
                if ($templateNameForGateway !== '' && shouldRetryWaTemplateWithName($message)) {
                    $templateResByName = $gw->sendTemplate(strval($no_penerima), $templateNameForGateway, $components);
                    $parsedByName = parseWaGatewayResponse($templateResByName, 'Gagal mengirim template WA');
                    if ($parsedByName->status === true || $parsedByName->status === 'true') {
                        myrbaRecordWaSentLogIfMissing($parsedByName->message_id ?? null, $normalizedTypePesan, $no_penerima, $parsedByName->raw ?? null, $tenantId, 'ivosight', $billingMode);

                        return $parsedByName;
                    }
                    $lastFailure = $parsedByName;
                }

                if (! isTemplateNotFoundError($message) && ! isTemplateParameterMismatchError($message)) {
                    return $lastFailure;
                }
            }

            if ($mustUseTemplate) {
                if ($firstMissingReport !== null) {
                    $detail = '';
                    if (! empty($firstMissingReport['missing_required'])) {
                        $detail = ' Missing: '.implode(', ', array_slice($firstMissingReport['missing_required'], 0, 8));
                    }

                    return (object) [
                        'status' => false,
                        'message' => 'Mapping template WA belum lengkap untuk message_type: '.$normalizedTypePesan.' (template_id: '.$firstMissingReport['template_id'].')'.$detail,
                        'raw' => null,
                    ];
                }

                if ($lastFailure !== null) {
                    return $lastFailure;
                }
            }
        }

        if ($mustUseTemplate) {
            return (object) [
                'status' => false,
                'message' => 'Mapping template WA tidak ditemukan untuk message_type: '.$normalizedTypePesan,
                'raw' => null,
            ];
        }
    }

    $legacyType = match ($normalizedTypePesan) {
        WaMessageTrigger::BILLING_REMINDER => 'tagihan',
        WaMessageTrigger::PAYMENT_RECEIPT => 'bayar',
        WaMessageTrigger::WELCOME_REGISTRATION => 'daftar',
        WaMessageTrigger::INVOICE_LINK => 'invoice',
        default => strtolower(trim((string) $typePesan)),
    };
    $legacyPayload = buildLegacyWaMessage($request, $legacyType);
    $message = str_replace(
        array_keys($legacyPayload['replacements']),
        array_values($legacyPayload['replacements']),
        $legacyPayload['template']
    );
    $res = $gw->sendText(strval($no_penerima), $message);
    $parsed = parseWaGatewayResponse($res, 'Gagal mengirim pesan WA');
    if ($parsed->status === true || $parsed->status === 'true') {
        myrbaRecordWaSentLogIfMissing($parsed->message_id ?? null, $normalizedTypePesan, $no_penerima, $parsed->raw ?? null, $tenantId, 'ivosight', $billingMode);
    }

    return $parsed;
}

function myrbaRecordWaSentLogIfMissing($messageId, string $type, $recipientId, $payload = null, ?int $tenantId = null, ?string $provider = null, ?string $billingMode = null): void
{
    $messageId = trim((string) ($messageId ?? ''));
    if ($messageId === '') {
        return;
    }
    $exists = WaMessageStatusLog::query()
        ->where('message_id', $messageId)
        ->where('status', 'sent')
        ->exists();
    if ($exists) {
        return;
    }
    WaMessageStatusLog::create([
        'tenant_id' => (int) ($tenantId ?? 1),
        'message_id' => $messageId,
        'recipient_id' => $recipientId !== null ? (string) $recipientId : null,
        'status' => 'sent',
        'type' => $type,
        'provider' => $provider !== null ? (string) $provider : null,
        'billing_mode' => $billingMode !== null ? (string) $billingMode : null,
        'cost_units' => 1,
        'status_at' => now(),
        'errors' => null,
        'payload' => is_array($payload) ? $payload : (is_object($payload) ? json_decode(json_encode($payload), true) : null),
    ]);
}

function getTenantLetterPrefix(int $tenantId): string
{
    $n = max(1, $tenantId);
    if ($n <= 26) {
        return chr(64 + $n);
    }

    return 'T';
}

function formatNoLayananTenant(?string $noLayanan, ?int $tenantId): string
{
    $n = trim((string) ($noLayanan ?? ''));
    $tid = (int) ($tenantId ?? 0);
    if ($n === '' || $tid <= 0) {
        return $n;
    }
    $p = getTenantLetterPrefix($tid);

    return $p.$n;
}

function parsePrefixedNoLayanan(string $value): array
{
    $s = trim($value);
    if ($s === '') {
        return [0, ''];
    }
    $first = substr($s, 0, 1);
    $rest = substr($s, 1);
    if (preg_match('/^[A-Z]$/i', $first) && preg_match('/^[0-9]+$/', $rest)) {
        $letter = strtoupper($first);
        $tenantId = ord($letter) - 64;

        return [$tenantId, $rest];
    }

    return [0, $s];
}

function resolveTenantIdForWaRequest($request): int
{
    try {
        if (Auth::check() && Auth::user() && isset(Auth::user()->tenant_id)) {
            return (int) Auth::user()->tenant_id;
        }
    } catch (\Throwable $e) {
    }

    if (is_object($request)) {
        if (isset($request->tenant_id) && is_numeric($request->tenant_id)) {
            return (int) $request->tenant_id;
        }

        if (isset($request->pelanggan_id) && is_numeric($request->pelanggan_id)) {
            try {
                $val = DBFacade::table('pelanggans')->where('id', (int) $request->pelanggan_id)->value('tenant_id');

                return (int) ($val ?? 1);
            } catch (\Throwable $e) {
            }
        }
    }

    return 1;
}

function buildLegacyWaMessage($request, $typePesan): array
{
    $configPesan = DB::table('config_pesan_notif')->first();
    if (! $configPesan) {
        throw new \Exception('Notification message configuration not found');
    }

    switch ($typePesan) {
        case 'bayar':
            return [
                'template' => $configPesan->pesan_notif_pembayaran,
                'replacements' => [
                    '{nama_pelanggan}' => $request->nama_pelanggan ?? $request->nama,
                    '{no_layanan}' => $request->no_layanan,
                    '{no_tagihan}' => $request->no_tagihan,
                    '{nominal}' => rupiah($request->nominal ?? $request->total_bayar),
                    '{metode_bayar}' => $request->metode_bayar,
                    '{tanggal_bayar}' => date('Y-m-d H:i:s'),
                    '{link_invoice}' => myrbaInvoiceSignedUrl($request->tagihan_id ?? $request->id),
                ],
            ];
        case 'tagihan':
            $due = null;
            if (! empty($request->periode) && ! empty($request->tanggal_daftar) && isset($request->jatuh_tempo)) {
                $due = myrbaTagihanDueDateFromPendaftaran($request->periode, $request->tanggal_daftar, $request->jatuh_tempo);
            }
            if (empty($due) && ! empty($request->tanggal_create_tagihan) && isset($request->jatuh_tempo)) {
                $due = addHari($request->tanggal_create_tagihan, $request->jatuh_tempo);
            }

            return [
                'template' => $configPesan->pesan_notif_tagihan,
                'replacements' => [
                    '{nama_perusahaan}' => getSettingWeb()->nama_perusahaan,
                    '{nama_pelanggan}' => $request->nama,
                    '{periode}' => tanggal_indonesia($request->periode),
                    '{no_layanan}' => $request->no_layanan,
                    '{total_bayar}' => rupiah($request->total_bayar),
                    '{tanggal_jatuh_tempo}' => $due,
                ],
            ];
        case 'daftar':
            $packageId = $request->paket_layanan ?? null;
            $paket = null;
            if (! empty($packageId) && is_numeric($packageId)) {
                $paket = DB::table('packages')->find((int) $packageId);
            }
            $user = Auth::user();

            return [
                'template' => $configPesan->pesan_notif_pendaftaran,
                'replacements' => [
                    '{nama_pelanggan}' => $request->nama,
                    '{alamat}' => $request->alamat,
                    '{paket_layanan}' => $paket->nama_layanan ?? '-',
                    '{no_layanan}' => $request->no_layanan,
                    '{no_wa}' => getSettingWeb()->no_wa,
                    '{email}' => getSettingWeb()->email,
                    '{nama_admin}' => $user->name ?? '-',
                    '{nama_perusahaan}' => getSettingWeb()->nama_perusahaan,
                ],
            ];
        case 'invoice':
            return [
                'template' => $configPesan->pesan_notif_kirim_invoice,
                'replacements' => [
                    '{nama_pelanggan}' => $request->nama,
                    '{no_layanan}' => $request->no_layanan,
                    '{no_tagihan}' => $request->no_tagihan,
                    '{nominal}' => rupiah($request->total_bayar),
                    '{metode_bayar}' => $request->metode_bayar,
                    '{tanggal_bayar}' => date('Y-m-d H:i:s'),
                    '{link_invoice}' => myrbaInvoiceSignedUrl($request->id),
                ],
            ];
        default:
            throw new \Exception("Unknown message type: {$typePesan}");
    }
}

function myrbaInvoiceSignedUrl($tagihanId): string
{
    if (empty($tagihanId)) {
        return '';
    }
    $minutes = (int) (env('INVOICE_SIGNED_TTL_MINUTES') ?: 1440);

    return URL::temporarySignedRoute('invoice.signed', now()->addMinutes($minutes), ['id' => (int) $tagihanId]);
}

function buildWaTemplateComponents($mappings, $request, string $typePesan): array
{
    $result = buildWaTemplateComponentsReport($mappings, $request, $typePesan);

    return $result['components'];
}

function resolvePreferredTemplateIdForTrigger(string $normalizedTypePesan, string $selectedType, $candidateMappings): string
{
    $selectedTypeMappings = $candidateMappings->filter(function ($row) use ($selectedType) {
        return strtolower(trim((string) $row->message_type)) === $selectedType;
    })->values();

    $fallbackTemplateId = (string) optional($selectedTypeMappings->first())->template_id;
    if ($fallbackTemplateId === '') {
        return '';
    }

    $forcedTemplateId = '';
    if ($normalizedTypePesan === WaMessageTrigger::BILLING_REMINDER) {
        $forcedTemplateId = trim((string) config('whatsapp.ivosight.template_id_billing_reminder'));
    } elseif ($normalizedTypePesan === WaMessageTrigger::PAYMENT_RECEIPT) {
        $forcedTemplateId = trim((string) config('whatsapp.ivosight.template_id_payment_receipt'));
    } elseif ($normalizedTypePesan === WaMessageTrigger::WELCOME_REGISTRATION) {
        $forcedTemplateId = trim((string) config('whatsapp.ivosight.template_id_welcome_registration'));
    } elseif ($normalizedTypePesan === WaMessageTrigger::INVOICE_LINK) {
        $forcedTemplateId = trim((string) config('whatsapp.ivosight.template_id_invoice_link'));
    } elseif ($normalizedTypePesan === WaMessageTrigger::BROADCAST) {
        $forcedTemplateId = trim((string) config('whatsapp.ivosight.template_id_broadcast'));
    }

    if ($forcedTemplateId !== '') {
        $existsForced = $candidateMappings->first(function ($row) use ($forcedTemplateId) {
            return (string) $row->template_id === $forcedTemplateId;
        });
        if ($existsForced) {
            return $forcedTemplateId;
        }
    }

    return $fallbackTemplateId;
}

function shouldRetryWaTemplateWithName(string $message): bool
{
    $m = strtolower(trim($message));
    if ($m === '') {
        return false;
    }

    return str_contains($m, 'template name does not exist')
        || str_contains($m, 'template id doest not exist')
        || str_contains($m, 'template id does not exist')
        || str_contains($m, 'template_id: the template id')
        || str_contains($m, 'template_id')
        || str_contains($m, '132001')
        || str_contains($m, 'template_name');
}

function isTemplateNotFoundError(string $message): bool
{
    $m = strtolower(trim($message));
    if ($m === '') {
        return false;
    }

    return str_contains($m, 'template id doest not exist')
        || str_contains($m, 'template id does not exist')
        || str_contains($m, 'template name does not exist')
        || str_contains($m, '132001');
}

function isTemplateParameterMismatchError(string $message): bool
{
    $m = strtolower(trim($message));
    if ($m === '') {
        return false;
    }

    return str_contains($m, 'parameter format does not match')
        || str_contains($m, '132012');
}

function resolveWaTemplateReferenceName($waTemplate): string
{
    if (! $waTemplate) {
        return '';
    }

    $payload = is_array($waTemplate->payload ?? null) ? $waTemplate->payload : [];
    $candidates = [
        $payload['template_name'] ?? null,
        $payload['name'] ?? null,
        $payload['label'] ?? null,
        $waTemplate->name ?? null,
    ];
    foreach ($candidates as $candidate) {
        $value = trim((string) $candidate);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function buildWaTemplateComponentsReport($mappings, $request, string $typePesan): array
{
    $grouped = [];
    $missingRequired = [];
    foreach ($mappings as $mapping) {
        $value = resolveWaTemplateMappingValue($mapping, $request, $typePesan);
        if ($value === null || $value === '') {
            if ($mapping->is_required === 'Yes') {
                $missingRequired[] = "{$mapping->source_key} [{$mapping->component_type}:{$mapping->param_index}]";
            }

            continue;
        }
        $componentType = strtolower((string) $mapping->component_type);
        $componentIndex = (int) ($mapping->component_index ?? 0);
        $componentSubType = strtolower((string) ($mapping->component_sub_type ?? ''));
        $groupKey = "{$componentType}|{$componentIndex}|{$componentSubType}";
        if (! isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [
                'type' => $componentType,
                'component_index' => $componentIndex,
                'component_sub_type' => $componentSubType,
                'parameters' => [],
            ];
        }
        $grouped[$groupKey]['parameters'][] = buildWaTemplateParameter($mapping->parameter_type, $value);
    }

    $components = [];
    foreach ($grouped as $item) {
        $component = [
            'type' => $item['type'],
            'parameters' => $item['parameters'],
        ];
        if ($item['type'] === 'button') {
            $component['index'] = (string) $item['component_index'];
            if ($item['component_sub_type'] !== '') {
                $component['sub_type'] = $item['component_sub_type'];
            }
        }
        if ($item['type'] === 'carousel') {
            $component['card_index'] = $item['component_index'];
        }
        $components[] = $component;
    }

    usort($components, function ($a, $b) {
        $priority = ['header' => 1, 'body' => 2, 'button' => 3, 'carousel' => 4];
        $ap = $priority[$a['type']] ?? 99;
        $bp = $priority[$b['type']] ?? 99;
        if ($ap !== $bp) {
            return $ap <=> $bp;
        }
        $ai = isset($a['index']) ? (int) $a['index'] : (isset($a['card_index']) ? (int) $a['card_index'] : 0);
        $bi = isset($b['index']) ? (int) $b['index'] : (isset($b['card_index']) ? (int) $b['card_index'] : 0);

        return $ai <=> $bi;
    });

    if (! empty($missingRequired)) {
        return [
            'components' => [],
            'missing_required' => array_values(array_unique($missingRequired)),
        ];
    }

    return [
        'components' => $components,
        'missing_required' => [],
    ];
}

function autoSendTagihanWa($tagihanId)
{
    $tagihan = DB::table('tagihans')
        ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
        ->select(
            'tagihans.*',
            'tagihans.id as id_tagihan',
            'pelanggans.nama',
            'pelanggans.no_wa',
            'pelanggans.no_layanan',
            'pelanggans.kirim_tagihan_wa',
            'pelanggans.jatuh_tempo',
            'pelanggans.tanggal_daftar'
        )
        ->where('tagihans.id', $tagihanId)
        ->first();

    if (! $tagihan) {
        return false;
    }

    // Cek konfigurasi global WA
    $waGateway = getWaGatewayActive();
    if ($waGateway->is_aktif !== 'Yes' || $waGateway->is_wa_billing_active !== 'Yes') {
        return false;
    }

    // Cek preferensi pelanggan
    if ($tagihan->kirim_tagihan_wa !== 'Yes' || empty($tagihan->no_wa)) {
        return false;
    }

    // Cek apakah sudah dibayar (jika dibayar, mungkin kirim notif bayar, bukan tagihan)
    if ($tagihan->status_bayar !== 'Belum Bayar') {
        return false;
    }

    try {
        $response = sendNotifWa('', $tagihan, 'billing_reminder', $tagihan->no_wa);
        if (isset($response->status) && ($response->status === true || $response->status === 'true')) {
            DB::table('tagihans')
                ->where('id', $tagihanId)
                ->update([
                    'is_send' => 'Yes',
                    'tanggal_kirim_notif_wa' => now(),
                    'updated_at' => now(),
                ]);

            return true;
        }
    } catch (\Throwable $e) {
        Log::error('Auto send WA tagihan failed', ['id' => $tagihanId, 'error' => $e->getMessage()]);
    }

    return false;
}

function autoSendWelcomeWa($pelangganId)
{
    $pelanggan = DB::table('pelanggans')
        ->select('id', 'nama', 'no_layanan', 'no_wa', 'alamat', 'email', 'paket_layanan')
        ->where('id', $pelangganId)
        ->first();

    if (! $pelanggan) {
        $messageId = 'welcome-'.(string) $pelangganId.'-'.uniqid();
        WaMessageStatusLog::create([
            'message_id' => $messageId,
            'recipient_id' => null,
            'status' => 'failed',
            'type' => WaMessageTrigger::WELCOME_REGISTRATION,
            'status_at' => now(),
            'errors' => [['message' => 'Pelanggan tidak ditemukan']],
            'payload' => ['pelanggan_id' => (int) $pelangganId],
        ]);

        return ['ok' => false, 'message' => 'Pelanggan tidak ditemukan', 'message_id' => $messageId];
    }

    if (empty($pelanggan->no_wa)) {
        $messageId = 'welcome-'.(string) $pelangganId.'-'.uniqid();
        WaMessageStatusLog::create([
            'message_id' => $messageId,
            'recipient_id' => null,
            'status' => 'failed',
            'type' => WaMessageTrigger::WELCOME_REGISTRATION,
            'status_at' => now(),
            'errors' => [['message' => 'Nomor WhatsApp pelanggan kosong']],
            'payload' => ['pelanggan_id' => (int) $pelangganId, 'no_layanan' => $pelanggan->no_layanan ?? null],
        ]);

        return ['ok' => false, 'message' => 'Nomor WhatsApp pelanggan kosong', 'message_id' => $messageId];
    }

    $waGateway = getWaGatewayActive();
    if ($waGateway->is_aktif !== 'Yes' || $waGateway->is_wa_welcome_active !== 'Yes') {
        $messageId = 'welcome-'.(string) $pelangganId.'-'.uniqid();
        WaMessageStatusLog::create([
            'message_id' => $messageId,
            'recipient_id' => (string) $pelanggan->no_wa,
            'status' => 'failed',
            'type' => WaMessageTrigger::WELCOME_REGISTRATION,
            'status_at' => now(),
            'errors' => [['message' => 'Gateway WA tidak aktif / welcome nonaktif']],
            'payload' => [
                'pelanggan_id' => (int) $pelangganId,
                'no_layanan' => $pelanggan->no_layanan ?? null,
                'is_aktif' => $waGateway->is_aktif ?? null,
                'is_wa_welcome_active' => $waGateway->is_wa_welcome_active ?? null,
            ],
        ]);

        return ['ok' => false, 'message' => 'Gateway WA tidak aktif / welcome nonaktif', 'message_id' => $messageId];
    }

    try {
        $response = sendNotifWa($waGateway->api_key ?? '', $pelanggan, 'welcome_registration', $pelanggan->no_wa);
        $ok = isset($response->status) && ($response->status === true || $response->status === 'true');
        if ($ok) {
            return [
                'ok' => true,
                'message' => 'OK',
                'message_id' => $response->message_id ?? null,
            ];
        }
        $raw = $response->raw ?? null;
        $rawArray = is_array($raw) ? $raw : json_decode(json_encode($raw), true);
        $messageId = $response->message_id ?? data_get($rawArray, 'messages.0.id') ?? ('welcome-'.(string) $pelangganId.'-'.uniqid());
        WaMessageStatusLog::create([
            'message_id' => $messageId,
            'recipient_id' => (string) $pelanggan->no_wa,
            'status' => 'failed',
            'type' => WaMessageTrigger::WELCOME_REGISTRATION,
            'status_at' => now(),
            'errors' => [['message' => $response->message ?? 'Notifikasi WA pendaftaran gagal dikirim']],
            'payload' => is_array($rawArray) ? $rawArray : ['raw' => $raw],
        ]);

        return [
            'ok' => false,
            'message' => $response->message ?? 'Notifikasi WA pendaftaran gagal dikirim',
            'message_id' => $messageId,
        ];
    } catch (\Throwable $e) {
        Log::error('Auto send WA welcome failed', ['pelanggan_id' => $pelangganId, 'error' => $e->getMessage()]);
        $messageId = 'welcome-'.(string) $pelangganId.'-'.uniqid();
        WaMessageStatusLog::create([
            'message_id' => $messageId,
            'recipient_id' => (string) $pelanggan->no_wa,
            'status' => 'failed',
            'type' => WaMessageTrigger::WELCOME_REGISTRATION,
            'status_at' => now(),
            'errors' => [['message' => $e->getMessage()]],
            'payload' => ['exception' => $e->getMessage()],
        ]);

        return ['ok' => false, 'message' => $e->getMessage(), 'message_id' => $messageId];
    }
}

function autoSendPaymentReceiptWa($tagihanId)
{
    $tagihan = DB::table('tagihans')
        ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
        ->select(
            'tagihans.*',
            'tagihans.id as tagihan_id',
            'pelanggans.nama',
            'pelanggans.no_wa',
            'pelanggans.no_layanan',
            'pelanggans.jatuh_tempo'
        )
        ->where('tagihans.id', $tagihanId)
        ->first();

    if (! $tagihan || empty($tagihan->no_wa)) {
        return false;
    }

    $waGateway = getWaGatewayActive();
    if ($waGateway->is_aktif !== 'Yes' || $waGateway->is_wa_payment_active !== 'Yes') {
        return false;
    }

    if (! in_array((string) ($tagihan->status_bayar ?? ''), ['Sudah Bayar', 'PAID', 'Paid'], true)) {
        return false;
    }

    try {
        $response = sendNotifWa('', $tagihan, 'payment_receipt', $tagihan->no_wa);

        return isset($response->status) && ($response->status === true || $response->status === 'true');
    } catch (\Throwable $e) {
        Log::error('Auto send WA payment receipt failed', ['tagihan_id' => $tagihanId, 'error' => $e->getMessage()]);

        return false;
    }
}

function applyReferralBonusIfEligible(int $pelangganId): bool
{
    $referrerId = DB::transaction(function () use ($pelangganId) {
        $pelanggan = DB::table('pelanggans')
            ->where('id', $pelangganId)
            ->lockForUpdate()
            ->first();

        if (! $pelanggan) {
            return null;
        }

        if (empty($pelanggan->kode_referal)) {
            return null;
        }

        if (! empty($pelanggan->referral_bonus_paid_at)) {
            return null;
        }

        if (($pelanggan->status_berlangganan ?? 'Menunggu') !== 'Aktif') {
            return null;
        }

        if (empty($pelanggan->paket_layanan) || ! is_numeric($pelanggan->paket_layanan)) {
            return null;
        }

        $package = DB::table('packages')
            ->select('id', 'referral_bonus')
            ->where('id', (int) $pelanggan->paket_layanan)
            ->first();

        $nominalReferal = (float) ($package->referral_bonus ?? 0);
        if ($nominalReferal <= 0) {
            return null;
        }

        $referrer = DB::table('pelanggans')
            ->where('no_layanan', (string) $pelanggan->kode_referal)
            ->lockForUpdate()
            ->first();

        if (! $referrer) {
            return null;
        }

        if ((int) $referrer->id === (int) $pelanggan->id) {
            return null;
        }

        $balanceBefore = (float) ($referrer->balance ?? 0);
        $balanceAfter = $balanceBefore + $nominalReferal;

        DB::table('pelanggans')
            ->where('id', $referrer->id)
            ->update([
                'balance' => $balanceAfter,
                'updated_at' => now(),
            ]);

        BalanceHistory::create([
            'pelanggan_id' => (int) $referrer->id,
            'type' => 'Penambahan',
            'amount' => $nominalReferal,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => "Penambahan balance dari fee referal pelanggan baru Bernama {$pelanggan->nama} dgn no layanan {$pelanggan->no_layanan} sebesar ".rupiah($nominalReferal),
        ]);

        DB::table('pelanggans')
            ->where('id', $pelanggan->id)
            ->update([
                'referral_bonus_paid_at' => now(),
                'updated_at' => now(),
            ]);

        return (int) $referrer->id;
    });

    if (! empty($referrerId)) {
        autoPayTagihanWithSaldo((int) $referrerId);

        return true;
    }

    return false;
}

function createTiketAduanForWithdraw(int $withdrawId): ?int
{
    return DB::transaction(function () use ($withdrawId) {
        $withdraw = DB::table('withdraws')
            ->where('id', $withdrawId)
            ->lockForUpdate()
            ->first();

        if (! $withdraw) {
            return null;
        }

        $tahun = date('Y');
        $prefix = "TKT-{$tahun}-";

        $lastTicket = DB::table('tiket_aduans')
            ->where('nomor_tiket', 'like', $prefix.'%')
            ->orderByDesc('nomor_tiket')
            ->lockForUpdate()
            ->first();

        $nextNumber = 1;
        if ($lastTicket && isset($lastTicket->nomor_tiket)) {
            $suffix = substr((string) $lastTicket->nomor_tiket, -6);
            if (ctype_digit($suffix)) {
                $nextNumber = ((int) $suffix) + 1;
            }
        }

        $nomorTiket = $prefix.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        $pelanggan = DB::table('pelanggans')
            ->select('id', 'nama', 'no_layanan')
            ->where('id', (int) $withdraw->pelanggan_id)
            ->first();

        $deskripsi = 'Permintaan withdraw saldo. ';
        if ($pelanggan) {
            $deskripsi .= 'Pelanggan: '.$pelanggan->nama.' ('.$pelanggan->no_layanan.'). ';
        }
        $deskripsi .= 'Nominal: '.rupiah((float) $withdraw->nominal_wd).'. ';
        $deskripsi .= 'Withdraw ID: '.(string) $withdraw->id.'.';

        $ticketId = DB::table('tiket_aduans')->insertGetId([
            'nomor_tiket' => $nomorTiket,
            'pelanggan_id' => (int) $withdraw->pelanggan_id,
            'deskripsi_aduan' => $deskripsi,
            'tanggal_aduan' => now(),
            'status' => 'Menunggu',
            'prioritas' => 'Sedang',
            'lampiran' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $ticketId;
    });
}

function applyInvestorSharingForPaidTagihan(int $tagihanId): bool
{
    if (! Schema::hasTable('investor_share_rules') || ! Schema::hasTable('investor_earnings')) {
        return false;
    }
    $tagihan = DB::table('tagihans')
        ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
        ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
        ->select(
            'tagihans.*',
            'tagihans.id as tagihan_id',
            'tagihans.no_tagihan as no_tagihan',
            'tagihans.periode as periode_tagihan',
            'pelanggans.id as pelanggan_id',
            'pelanggans.nama as nama_pelanggan',
            'pelanggans.no_layanan',
            'pelanggans.coverage_area',
            'pelanggans.paket_layanan',
            'packages.harga as harga_paket'
        )
        ->where('tagihans.id', $tagihanId)
        ->first();
    if (! $tagihan) {
        return false;
    }
    $statusBayar = strtolower(trim((string) ($tagihan->status_bayar ?? '')));
    if (! in_array($statusBayar, ['sudah bayar', 'paid', 'lunas'], true)) {
        return false;
    }
    $periode = trim((string) ($tagihan->periode_tagihan ?? ''));
    $pelangganId = (int) ($tagihan->pelanggan_id ?? 0);
    if ($pelangganId > 0) {
        $firstPeriode = (string) (DB::table('tagihans')->where('pelanggan_id', $pelangganId)->min('periode') ?? '');
        if ($firstPeriode !== '' && $periode !== '' && $periode === $firstPeriode) {
            return false;
        }
    }
    $baseTotal = (float) ($tagihan->total_bayar ?? 0);
    $baseHargaPaket = (float) ($tagihan->harga_paket ?? 0);
    $rules = DB::table('investor_share_rules')
        ->where('is_aktif', 'Yes')
        ->get();
    if ($rules->isEmpty()) {
        return false;
    }
    $includedPelangganByRule = [];
    if (Schema::hasTable('investor_share_rule_pelanggans')) {
        $ruleIds = $rules->pluck('id')->map(fn ($v) => (int) $v)->all();
        if (! empty($ruleIds)) {
            $rows = DB::table('investor_share_rule_pelanggans')
                ->select('rule_id', 'pelanggan_id')
                ->whereIn('rule_id', $ruleIds)
                ->where('is_included', 'Yes')
                ->get();
            foreach ($rows as $row) {
                $rid = (int) $row->rule_id;
                if (! isset($includedPelangganByRule[$rid])) {
                    $includedPelangganByRule[$rid] = [];
                }
                $includedPelangganByRule[$rid][] = (int) $row->pelanggan_id;
            }
        }
    }
    foreach ($rules as $rule) {
        $match = false;
        $ruleId = (int) ($rule->id ?? 0);
        $manualList = $includedPelangganByRule[$ruleId] ?? [];
        if (! empty($manualList)) {
            $match = in_array((int) ($tagihan->pelanggan_id ?? 0), $manualList, true);
        } else {
            if ($rule->rule_type === 'per_customer') {
                $match = true;
            } elseif ($rule->rule_type === 'per_area' && ! empty($rule->coverage_area_id)) {
                $match = (int) $rule->coverage_area_id === (int) ($tagihan->coverage_area ?? 0);
            } elseif ($rule->rule_type === 'per_package' && ! empty($rule->package_id)) {
                $match = (int) $rule->package_id === (int) ($tagihan->paket_layanan ?? 0);
            }
        }
        if (! $match) {
            continue;
        }
        $startPeriod = trim((string) ($rule->start_period ?? ''));
        if ($startPeriod !== '' && $periode !== '' && strcmp($periode, $startPeriod) < 0) {
            continue;
        }
        $amount = 0.0;
        if ($rule->amount_type === 'percent') {
            $base = $baseTotal > 0 ? $baseTotal : ($baseHargaPaket > 0 ? $baseHargaPaket : 0);
            $amount = round($base * ((float) $rule->amount_value) / 100.0, 2);
        } else {
            $amount = (float) $rule->amount_value;
        }
        if ($amount <= 0) {
            continue;
        }
        DBFacade::transaction(function () use ($rule, $amount, $tagihan, $periode) {
            $inserted = DB::table('investor_earnings')->insertOrIgnore([
                'user_id' => (int) $rule->user_id,
                'rule_id' => (int) $rule->id,
                'pelanggan_id' => (int) ($tagihan->pelanggan_id ?? 0) ?: null,
                'tagihan_id' => (int) ($tagihan->tagihan_id ?? 0) ?: null,
                'periode' => $periode !== '' ? $periode : null,
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (! $inserted) {
                return;
            }
            $wallet = DB::table('investor_wallets')->where('user_id', (int) $rule->user_id)->lockForUpdate()->first();
            if (! $wallet) {
                DB::table('investor_wallets')->insert([
                    'user_id' => (int) $rule->user_id,
                    'balance' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $wallet = DB::table('investor_wallets')->where('user_id', (int) $rule->user_id)->lockForUpdate()->first();
            }
            $before = (float) ($wallet->balance ?? 0);
            $after = $before + $amount;
            DB::table('investor_wallets')->where('user_id', (int) $rule->user_id)->update([
                'balance' => $after,
                'updated_at' => now(),
            ]);
            DB::table('investor_wallet_histories')->insert([
                'user_id' => (int) $rule->user_id,
                'type' => 'Credit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => 'Bagi hasil tagihan '.($tagihan->no_tagihan ?? '-').' pelanggan '.($tagihan->nama_pelanggan ?? '-').' ('.($tagihan->no_layanan ?? '-').')',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    return true;
}

function applyInvestorSharingForPaidTagihanForRule(int $tagihanId, int $ruleId): bool
{
    if (! Schema::hasTable('investor_share_rules') || ! Schema::hasTable('investor_earnings')) {
        return false;
    }
    $rule = DB::table('investor_share_rules')
        ->where('id', (int) $ruleId)
        ->where('is_aktif', 'Yes')
        ->first();
    if (! $rule) {
        return false;
    }

    $tagihan = DB::table('tagihans')
        ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
        ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
        ->select(
            'tagihans.*',
            'tagihans.id as tagihan_id',
            'tagihans.no_tagihan as no_tagihan',
            'tagihans.periode as periode_tagihan',
            'pelanggans.id as pelanggan_id',
            'pelanggans.nama as nama_pelanggan',
            'pelanggans.no_layanan',
            'pelanggans.coverage_area',
            'pelanggans.paket_layanan',
            'packages.harga as harga_paket'
        )
        ->where('tagihans.id', $tagihanId)
        ->first();
    if (! $tagihan) {
        return false;
    }

    $statusBayar = strtolower(trim((string) ($tagihan->status_bayar ?? '')));
    if (! in_array($statusBayar, ['sudah bayar', 'paid', 'lunas'], true)) {
        return false;
    }

    $periode = trim((string) ($tagihan->periode_tagihan ?? ''));
    $pelangganId = (int) ($tagihan->pelanggan_id ?? 0);
    if ($pelangganId > 0) {
        $firstPeriode = (string) (DB::table('tagihans')->where('pelanggan_id', $pelangganId)->min('periode') ?? '');
        if ($firstPeriode !== '' && $periode !== '' && $periode === $firstPeriode) {
            return false;
        }
    }
    $startPeriod = trim((string) ($rule->start_period ?? ''));
    if ($startPeriod !== '' && $periode !== '' && strcmp($periode, $startPeriod) < 0) {
        return false;
    }

    $manualList = [];
    if (Schema::hasTable('investor_share_rule_pelanggans')) {
        $manualList = DB::table('investor_share_rule_pelanggans')
            ->where('rule_id', (int) $ruleId)
            ->where('is_included', 'Yes')
            ->pluck('pelanggan_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    $match = false;
    if (! empty($manualList)) {
        $match = in_array((int) ($tagihan->pelanggan_id ?? 0), $manualList, true);
    } else {
        if ($rule->rule_type === 'per_customer') {
            $match = true;
        } elseif ($rule->rule_type === 'per_area' && ! empty($rule->coverage_area_id)) {
            $match = (int) $rule->coverage_area_id === (int) ($tagihan->coverage_area ?? 0);
        } elseif ($rule->rule_type === 'per_package' && ! empty($rule->package_id)) {
            $match = (int) $rule->package_id === (int) ($tagihan->paket_layanan ?? 0);
        }
    }
    if (! $match) {
        return false;
    }

    $baseTotal = (float) ($tagihan->total_bayar ?? 0);
    $baseHargaPaket = (float) ($tagihan->harga_paket ?? 0);
    $amount = 0.0;
    if ($rule->amount_type === 'percent') {
        $base = $baseTotal > 0 ? $baseTotal : ($baseHargaPaket > 0 ? $baseHargaPaket : 0);
        $amount = round($base * ((float) $rule->amount_value) / 100.0, 2);
    } else {
        $amount = (float) $rule->amount_value;
    }
    if ($amount <= 0) {
        return false;
    }

    $credited = false;
    DBFacade::transaction(function () use ($rule, $ruleId, $amount, $tagihan, $periode, &$credited) {
        $inserted = DB::table('investor_earnings')->insertOrIgnore([
            'user_id' => (int) $rule->user_id,
            'rule_id' => (int) $ruleId,
            'pelanggan_id' => (int) ($tagihan->pelanggan_id ?? 0) ?: null,
            'tagihan_id' => (int) ($tagihan->tagihan_id ?? 0) ?: null,
            'periode' => $periode !== '' ? $periode : null,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (! $inserted) {
            $credited = false;

            return;
        }
        $wallet = DB::table('investor_wallets')->where('user_id', (int) $rule->user_id)->lockForUpdate()->first();
        if (! $wallet) {
            DB::table('investor_wallets')->insert([
                'user_id' => (int) $rule->user_id,
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wallet = DB::table('investor_wallets')->where('user_id', (int) $rule->user_id)->lockForUpdate()->first();
        }
        $before = (float) ($wallet->balance ?? 0);
        $after = $before + $amount;
        DB::table('investor_wallets')->where('user_id', (int) $rule->user_id)->update([
            'balance' => $after,
            'updated_at' => now(),
        ]);
        DB::table('investor_wallet_histories')->insert([
            'user_id' => (int) $rule->user_id,
            'type' => 'Credit',
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'description' => 'Bagi hasil tagihan '.($tagihan->no_tagihan ?? '-').' pelanggan '.($tagihan->nama_pelanggan ?? '-').' ('.($tagihan->no_layanan ?? '-').')',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $credited = true;
    });

    return $credited;
}

function resolveWaTemplateMappingValue($mapping, $request, string $typePesan)
{
    $req = json_decode(json_encode($request), true) ?: [];
    $setting = json_decode(json_encode(getSettingWeb()), true) ?: [];
    $packageName = $req['nama_layanan'] ?? null;
    $packageId = $req['paket_layanan'] ?? null;
    if (($packageName === null || $packageName === '') && ! empty($packageId) && is_numeric($packageId)) {
        $packageName = DB::table('packages')->where('id', (int) $packageId)->value('nama_layanan');
    }
    $tanggalJatuhTempo = $req['tanggal_jatuh_tempo'] ?? null;
    if (($tanggalJatuhTempo === null || $tanggalJatuhTempo === '') && ! empty($req['periode']) && ! empty($req['tanggal_daftar']) && isset($req['jatuh_tempo'])) {
        $tanggalJatuhTempo = myrbaTagihanDueDateFromPendaftaran($req['periode'], $req['tanggal_daftar'], $req['jatuh_tempo']);
    }
    if (($tanggalJatuhTempo === null || $tanggalJatuhTempo === '') && ! empty($req['tanggal_create_tagihan']) && isset($req['jatuh_tempo'])) {
        $tanggalJatuhTempo = addHari($req['tanggal_create_tagihan'], $req['jatuh_tempo']);
    }
    $tanggalBayar = $req['tanggal_bayar'] ?? null;
    if (($tanggalBayar === null || $tanggalBayar === '') && in_array($typePesan, ['bayar', 'invoice'], true)) {
        $tanggalBayar = date('Y-m-d H:i:s');
    }
    if (($tanggalBayar === null || $tanggalBayar === '') && $typePesan === 'tagihan') {
        $tanggalBayar = '-';
    }
    $tagihanId = $req['tagihan_id'] ?? ($req['id'] ?? null);
    $linkInvoice = $req['link_invoice'] ?? null;
    if (($linkInvoice === null || $linkInvoice === '') && ! empty($tagihanId)) {
        $linkInvoice = myrbaInvoiceSignedUrl($tagihanId);
    }
    if ($linkInvoice !== null && $linkInvoice !== '') {
        $linkInvoice = trim((string) $linkInvoice);
        $linkInvoice = trim($linkInvoice, "` \t\n\r\0\x0B");
    }
    $pelangganContext = array_merge($req, [
        'paket_layanan_nama' => $packageName,
        'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
        'tanggal_bayar' => $tanggalBayar,
        'link_invoice' => $linkInvoice,
    ]);
    $tagihanContext = array_merge($req, [
        'paket_layanan_nama' => $packageName,
        'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
        'jatuh_tempo_tanggal' => $tanggalJatuhTempo,
        'tanggal_bayar' => $tanggalBayar,
        'link_invoice' => $linkInvoice,
    ]);
    $context = array_merge($req, [
        'request' => $req,
        'pelanggan' => $pelangganContext,
        'tagihan' => $tagihanContext,
        'setting' => $setting,
        'brand_name' => $setting['nama_perusahaan'] ?? null,
        'nama_perusahaan' => $setting['nama_perusahaan'] ?? null,
        'paket_layanan_nama' => $packageName,
        'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
        'jatuh_tempo_tanggal' => $tanggalJatuhTempo,
        'tanggal_bayar' => $tanggalBayar,
        'link_invoice' => $linkInvoice,
        'type_pesan' => $typePesan,
    ]);

    $sourceKey = trim((string) $mapping->source_key);
    $normalizedSourceKey = trim($sourceKey, "{} \t\n\r\0\x0B");
    $candidateKeys = array_values(array_unique(array_filter([
        $sourceKey,
        $normalizedSourceKey,
    ])));
    if (strpos($normalizedSourceKey, '.') === false && $normalizedSourceKey !== '') {
        $aliasMap = [
            'nama_pelanggan' => 'pelanggan.nama',
            'nama' => 'pelanggan.nama',
            'no_layanan' => 'pelanggan.no_layanan',
            'no_wa' => 'pelanggan.no_wa',
            'alamat' => 'pelanggan.alamat',
            'email' => 'pelanggan.email',
            'paket_layanan' => 'pelanggan.paket_layanan_nama',
            'paket_layanan_nama' => 'pelanggan.paket_layanan_nama',
            'no_tagihan' => 'tagihan.no_tagihan',
            'total_bayar' => 'tagihan.total_bayar',
            'nominal_bayar' => 'tagihan.nominal_bayar',
            'periode' => 'tagihan.periode',
            'metode_bayar' => 'tagihan.metode_bayar',
            'tanggal_bayar' => 'tagihan.tanggal_bayar',
            'tanggal_jatuh_tempo' => 'tagihan.tanggal_jatuh_tempo',
            'jatuh_tempo' => 'tagihan.jatuh_tempo',
            'link_invoice' => 'tagihan.link_invoice',
            'nama_perusahaan' => 'setting.nama_perusahaan',
            'no_wa_perusahaan' => 'setting.no_wa',
        ];
        $candidateKeys[] = "request.{$normalizedSourceKey}";
        $candidateKeys[] = "tagihan.{$normalizedSourceKey}";
        $candidateKeys[] = "pelanggan.{$normalizedSourceKey}";
        $candidateKeys[] = "setting.{$normalizedSourceKey}";
        if (isset($aliasMap[$normalizedSourceKey])) {
            $candidateKeys[] = $aliasMap[$normalizedSourceKey];
        }
    }
    $candidateKeys = array_values(array_unique($candidateKeys));
    $value = null;
    foreach ($candidateKeys as $candidateKey) {
        $candidateValue = data_get($context, $candidateKey);
        if (($candidateValue === null || $candidateValue === '') && array_key_exists($candidateKey, $context)) {
            $candidateValue = $context[$candidateKey];
        }
        if ($candidateValue !== null && $candidateValue !== '') {
            $value = $candidateValue;
            break;
        }
    }
    if (($value === null || $value === '') && ! empty($mapping->default_value)) {
        $value = $mapping->default_value;
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
        return (string) $value;
    }

    return null;
}

function buildWaTemplateParameter(string $parameterType, string $value): array
{
    if ($parameterType === 'image') {
        return ['type' => 'image', 'image' => ['link' => $value]];
    }
    if ($parameterType === 'document') {
        return ['type' => 'document', 'document' => ['link' => $value, 'filename' => basename(parse_url($value, PHP_URL_PATH) ?? 'document')]];
    }
    if ($parameterType === 'video') {
        return ['type' => 'video', 'video' => ['link' => $value]];
    }
    if ($parameterType === 'payload') {
        return ['type' => 'payload', 'payload' => $value];
    }
    if ($parameterType === 'action') {
        $decoded = json_decode($value, true);

        return ['type' => 'action', 'action' => is_array($decoded) ? $decoded : ['flow_token' => $value]];
    }
    if ($parameterType === 'product') {
        $decoded = json_decode($value, true);

        return ['type' => 'product', 'product' => is_array($decoded) ? $decoded : ['product_retailer_id' => $value]];
    }

    return ['type' => 'text', 'text' => $value];
}

function parseWaGatewayResponse($response, string $defaultMessage): object
{
    $rawBody = $response->body();
    $bodyArray = json_decode($rawBody, true);
    $body = is_array($bodyArray) ? (object) $bodyArray : json_decode($rawBody);
    $messageId = data_get($bodyArray, 'messages.0.id')
        ?? data_get($bodyArray, 'data.message_id')
        ?? data_get($bodyArray, 'message_id');
    $statusFromBody = data_get($bodyArray, 'status');
    $successFromBody = data_get($bodyArray, 'success');
    $errors = data_get($bodyArray, 'errors');
    $hasErrors = (is_array($errors) && ! empty($errors)) || isset($bodyArray['error']);

    $isSuccess = false;
    if ($response->successful()) {
        if (is_bool($successFromBody)) {
            $isSuccess = $successFromBody;
        } elseif (is_string($successFromBody)) {
            $isSuccess = in_array(strtolower($successFromBody), ['true', 'ok', 'success', 'sent', 'queued', 'accepted'], true);
        } elseif (is_bool($statusFromBody)) {
            $isSuccess = $statusFromBody;
        } elseif (is_string($statusFromBody)) {
            $isSuccess = in_array(strtolower($statusFromBody), ['true', 'ok', 'success', 'sent', 'queued', 'accepted'], true);
        } elseif (! empty(data_get($bodyArray, 'messages')) || ! empty($messageId)) {
            $isSuccess = true;
        }
        if ($hasErrors) {
            $isSuccess = false;
        }
    }

    if ($isSuccess) {
        return (object) [
            'status' => true,
            'message' => 'OK',
            'message_id' => $messageId,
            'raw' => $body,
        ];
    }

    $errorMessage = resolveWaGatewayErrorMessage($bodyArray, $defaultMessage);
    if ($errorMessage === $defaultMessage) {
        $rawExcerpt = trim(preg_replace('/\s+/', ' ', strip_tags((string) $rawBody)));
        if ($rawExcerpt !== '') {
            $errorMessage .= ' - '.mb_substr($rawExcerpt, 0, 300);
        }
    }
    if ($errorMessage === $defaultMessage && method_exists($response, 'status')) {
        $statusCode = (int) $response->status();
        if ($statusCode > 0) {
            $errorMessage .= ' [HTTP '.$statusCode.']';
        }
    }

    return (object) [
        'status' => false,
        'message' => $errorMessage,
        'raw' => $body,
    ];
}

function resolveWaGatewayErrorMessage(?array $bodyArray, string $defaultMessage): string
{
    if (! is_array($bodyArray)) {
        return $defaultMessage;
    }

    $candidates = [
        data_get($bodyArray, 'message'),
        data_get($bodyArray, 'error.message'),
        data_get($bodyArray, 'errors.0.message'),
        data_get($bodyArray, 'errors.0.detail'),
        data_get($bodyArray, 'data.error.message'),
        data_get($bodyArray, 'meta.message'),
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    $firstError = data_get($bodyArray, 'errors.0');
    if (is_array($firstError)) {
        $flatten = [];
        foreach (['code', 'title', 'detail'] as $key) {
            $value = $firstError[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $flatten[] = trim($value);
            }
        }
        if (! empty($flatten)) {
            return implode(' | ', $flatten);
        }
    }

    $errorsMap = data_get($bodyArray, 'errors');
    if (is_array($errorsMap)) {
        foreach ($errorsMap as $field => $messages) {
            if (is_array($messages) && ! empty($messages)) {
                $first = $messages[0] ?? null;
                if (is_string($first) && trim($first) !== '') {
                    return trim((string) $field).': '.trim($first);
                }
            } elseif (is_string($messages) && trim($messages) !== '') {
                return trim((string) $field).': '.trim($messages);
            }
        }
    }

    return $defaultMessage;
}

function totalStatusBayar($status)
{
    $allowedAreas = getAllowedAreaCoverageIdsForUser();
    $query = DB::table('tagihans')
        ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
        ->where('tagihans.status_bayar', $status);
    if (! empty($allowedAreas)) {
        $query->whereIn('pelanggans.coverage_area', $allowedAreas);
    } else {
        return 0;
    }

    return $query->count();
}

function addHari($tgl, $jatuh_tempo)
{
    $tgl = date('Y-m-d', strtotime('+'.$jatuh_tempo.'days', strtotime($tgl)));

    return $tgl;
}

function myrbaTagihanDueDateFromPendaftaran($periode, $tanggalDaftar, $tambahanHari)
{
    try {
        $periode = trim((string) $periode);
        if (! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            return null;
        }
        $signup = \Carbon\Carbon::parse($tanggalDaftar);
        $signupDay = (int) $signup->format('d');
        $base = \Carbon\Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $lastDay = (int) $base->daysInMonth;
        $baseDay = $signupDay > $lastDay ? $lastDay : $signupDay;
        $base = $base->day($baseDay)->startOfDay();
        $due = $base->copy()->addDays((int) $tambahanHari);

        return $due->format('Y-m-d');
    } catch (\Throwable $e) {
        return null;
    }
}

function myrbaUpcomingBillingPeriodForPelanggan($tanggalDaftar, $tambahanHari, $fromDate = null)
{
    try {
        $from = $fromDate ? \Carbon\Carbon::parse($fromDate)->startOfDay() : now()->startOfDay();
        $startMonth = $from->copy()->startOfMonth();
        $candidates = [];
        for ($i = 0; $i < 3; $i++) {
            $periode = $startMonth->copy()->addMonths($i)->format('Y-m');
            $dueStr = myrbaTagihanDueDateFromPendaftaran($periode, $tanggalDaftar, $tambahanHari);
            if (! $dueStr) {
                continue;
            }
            $due = \Carbon\Carbon::parse($dueStr)->startOfDay();
            if ($due->greaterThanOrEqualTo($from)) {
                $candidates[] = ['periode' => $due->format('Y-m'), 'due_date' => $due->toDateString(), 'due' => $due];
            }
        }
        if (empty($candidates)) {
            $periode = $startMonth->copy()->addMonths(1)->format('Y-m');
            $dueStr = myrbaTagihanDueDateFromPendaftaran($periode, $tanggalDaftar, $tambahanHari);
            if (! $dueStr) {
                return null;
            }
            $due = \Carbon\Carbon::parse($dueStr)->startOfDay();

            return ['periode' => $due->format('Y-m'), 'due_date' => $due->toDateString()];
        }
        usort($candidates, function ($a, $b) {
            return $a['due']->timestamp <=> $b['due']->timestamp;
        });

        return ['periode' => $candidates[0]['periode'], 'due_date' => $candidates[0]['due_date']];
    } catch (\Throwable $e) {
        return null;
    }
}

function myrbaNextBillingPeriodForPelanggan($tanggalDaftar, $tambahanHari, $fromDate = null)
{
    try {
        $upcoming = myrbaUpcomingBillingPeriodForPelanggan($tanggalDaftar, $tambahanHari, $fromDate);
        if (! $upcoming || empty($upcoming['due_date'])) {
            return null;
        }
        $after = \Carbon\Carbon::parse($upcoming['due_date'])->addDay()->startOfDay();
        $startMonth = $after->copy()->startOfMonth();
        $candidates = [];
        for ($i = 0; $i < 4; $i++) {
            $periode = $startMonth->copy()->addMonths($i)->format('Y-m');
            $dueStr = myrbaTagihanDueDateFromPendaftaran($periode, $tanggalDaftar, $tambahanHari);
            if (! $dueStr) {
                continue;
            }
            $due = \Carbon\Carbon::parse($dueStr)->startOfDay();
            if ($due->greaterThanOrEqualTo($after)) {
                $candidates[] = ['periode' => $due->format('Y-m'), 'due_date' => $due->toDateString(), 'due' => $due];
            }
        }
        if (empty($candidates)) {
            $periode = $startMonth->copy()->addMonths(1)->format('Y-m');
            $dueStr = myrbaTagihanDueDateFromPendaftaran($periode, $tanggalDaftar, $tambahanHari);
            if (! $dueStr) {
                return null;
            }
            $due = \Carbon\Carbon::parse($dueStr)->startOfDay();

            return ['periode' => $due->format('Y-m'), 'due_date' => $due->toDateString()];
        }
        usort($candidates, function ($a, $b) {
            return $a['due']->timestamp <=> $b['due']->timestamp;
        });

        return ['periode' => $candidates[0]['periode'], 'due_date' => $candidates[0]['due_date']];
    } catch (\Throwable $e) {
        return null;
    }
}

function hitungUang($type)
{
    if ($type == 'Pemasukan') {
        $pemasukan = DB::table('pemasukans')

            ->sum('pemasukans.nominal');

        return $pemasukan;
    } else {
        $pengeluaran = DB::table('pengeluarans')

            ->sum('pengeluarans.nominal');

        return $pengeluaran;
    }
}

function tanggal_indonesia($tanggal)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];

    $pecahkan = explode('-', $tanggal);

    return $bulan[(int) $pecahkan[1]].' '.$pecahkan[0];
}

function randN($length)
{
    $chars = '23456789';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function randUC($length)
{
    $chars = 'ABCDEFGHJKLMNPRSTUVWXYZ';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function randLC($length)
{
    $chars = 'abcdefghijkmnprstuvwxyz';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function randULC($length)
{
    $chars = 'ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnprstuvwxyz';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function randNLC($length)
{
    $chars = '23456789abcdefghijkmnprstuvwxyz';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function randNUC($length)
{
    $chars = '23456789ABCDEFGHJKLMNPRSTUVWXYZ';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function permissionAreaCoverageAccessName($areaId)
{
    return 'area coverage access:'.$areaId;
}

function getAllowedAreaCoverageIdsForUser()
{
    $user = Auth::user();
    if (! $user) {
        return [];
    }
    if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
        return AreaCoverage::pluck('id')->toArray();
    }
    $names = $user->getAllPermissions()->pluck('name')->toArray();
    if (in_array('area coverage access:all', $names, true)) {
        return AreaCoverage::pluck('id')->toArray();
    }
    $ids = [];
    foreach ($names as $name) {
        if (str_starts_with($name, 'area coverage access:')) {
            $parts = explode(':', $name, 2);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $ids[] = (int) $parts[1];
            }
        }
    }

    return array_values(array_unique($ids));
}

function getInternetIncomeCategoryIdForPelanggan($pelangganId)
{
    $tenantId = 1;
    try {
        $val = DBFacade::table('pelanggans')->where('id', (int) $pelangganId)->value('tenant_id');
        $tenantId = (int) ($val ?? 1);
        if ($tenantId < 1) {
            $tenantId = 1;
        }
    } catch (\Throwable $e) {
        $tenantId = 1;
    }

    $info = DB::table('pelanggans')
        ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
        ->select('pelanggans.coverage_area', 'area_coverages.nama', 'area_coverages.kode_area')
        ->where('pelanggans.id', $pelangganId)
        ->where('pelanggans.tenant_id', $tenantId)
        ->where('area_coverages.tenant_id', $tenantId)
        ->first();
    $areaName = $info ? ($info->nama ?: $info->kode_area ?: 'Tidak Diketahui') : 'Tidak Diketahui';
    $categoryName = 'Pemasukan internet - '.$areaName;
    $existing = DB::table('category_pemasukans')->where('tenant_id', $tenantId)->where('nama_kategori_pemasukan', $categoryName)->first();
    if ($existing) {
        return $existing->id;
    }
    $model = CategoryPemasukan::create(['tenant_id' => $tenantId, 'nama_kategori_pemasukan' => $categoryName]);

    return $model->id;
}

function getSaldoIncomeCategoryId()
{
    $tenantId = (int) (Auth::user()->tenant_id ?? 1);
    if ($tenantId < 1) {
        $tenantId = 1;
    }
    $categoryName = 'Saldo';
    $existing = DB::table('category_pemasukans')->where('tenant_id', $tenantId)->where('nama_kategori_pemasukan', $categoryName)->first();
    if ($existing) {
        return $existing->id;
    }
    $model = CategoryPemasukan::create(['tenant_id' => $tenantId, 'nama_kategori_pemasukan' => $categoryName]);

    return $model->id;
}

function autoPayTagihanWithSaldo($pelangganId)
{
    $pelanggan = DB::table('pelanggans')
        ->select('id', 'tenant_id', 'balance', 'nama')
        ->where('id', $pelangganId)
        ->first();
    if (! $pelanggan) {
        return 0;
    }
    $tenantId = (int) ($pelanggan->tenant_id ?? 1);
    if ($tenantId < 1) {
        $tenantId = 1;
    }
    $balance = is_numeric($pelanggan->balance) ? (float) $pelanggan->balance : 0;
    if ($balance <= 0) {
        return 0;
    }
    $tagihans = DB::table('tagihans')
        ->where('pelanggan_id', $pelangganId)
        ->where('tenant_id', $tenantId)
        ->where('status_bayar', 'Belum Bayar')
        ->orderBy('tanggal_create_tagihan', 'asc')
        ->orderBy('id', 'asc')
        ->get();
    $paidCount = 0;
    foreach ($tagihans as $tagihan) {
        $total = is_numeric($tagihan->total_bayar) ? (float) $tagihan->total_bayar : 0;
        if ($total <= 0 || $balance < $total) {
            break;
        }
        DB::table('tagihans')
            ->where('id', $tagihan->id)
            ->where('tenant_id', $tenantId)
            ->update([
                'status_bayar' => 'Sudah Bayar',
                'metode_bayar' => 'Saldo',
                'tanggal_bayar' => now(),
                'tanggal_kirim_notif_wa' => now(),
            ]);
        autoSendPaymentReceiptWa($tagihan->id);
        $categoryId = getSaldoIncomeCategoryId();
        DB::table('pemasukans')->insert([
            'tenant_id' => $tenantId,
            'nominal' => $total,
            'tanggal' => now(),
            'category_pemasukan_id' => $categoryId,
            'referense_id' => $tagihan->id,
            'metode_bayar' => 'Saldo',
            'keterangan' => 'Pembayaran Tagihan no Tagihan '.$tagihan->no_tagihan.' a/n '.$pelanggan->nama.' Periode '.$tagihan->periode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $balanceAfter = $balance - $total;
        DB::table('pelanggans')
            ->where('id', $pelangganId)
            ->where('tenant_id', $tenantId)
            ->update(['balance' => $balanceAfter]);
        DB::table('balance_histories')->insert([
            'tenant_id' => $tenantId,
            'pelanggan_id' => $pelangganId,
            'type' => 'Pengurangan',
            'amount' => $total,
            'balance_before' => $balance,
            'balance_after' => $balanceAfter,
            'description' => 'Pembayaran Tagihan #'.$tagihan->no_tagihan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $balance = $balanceAfter;
        $paidCount++;
    }
    if ($paidCount > 0) {
        $unpaidCount = DB::table('tagihans')
            ->where('pelanggan_id', $pelangganId)
            ->where('status_bayar', 'Belum Bayar')
            ->count();
        if ($unpaidCount < 1) {
            DB::table('pelanggans')
                ->where('id', $pelangganId)
                ->update(['status_berlangganan' => 'Aktif']);
        }
    }

    return $paidCount;
}

function randNULC($length)
{
    $chars = '23456789ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnprstuvwxyz';
    $charArray = str_split($chars);
    $charCount = strlen($chars);
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $randChar = rand(0, $charCount - 1);
        $result .= $charArray[$randChar];
    }

    return $result;
}

function getSettingWeb()
{
    return DB::table('setting_web')
        ->first();
}

function resolveTripayConfigForTenantId(int $tenantId): ?array
{
    if ($tenantId < 1) {
        return null;
    }
    if (! class_exists(\App\Models\Tenant::class)) {
        return null;
    }

    $tenant = \App\Models\Tenant::query()->find($tenantId);
    if (! $tenant) {
        return null;
    }

    $providerMode = strtolower((string) ($tenant->tripay_provider_mode ?? 'owner'));
    $gatewayMode = $providerMode === 'tenant' ? 'tenant' : 'owner';
    if ($gatewayMode === 'owner') {
        $settingWeb = getSettingWeb();
        $baseUrl = trim((string) ($settingWeb->url_tripay ?? ''));
        $apiKey = trim((string) ($settingWeb->api_key_tripay ?? ''));
        $merchantCode = trim((string) ($settingWeb->kode_merchant ?? ''));
        $privateKey = trim((string) ($settingWeb->private_key ?? ''));
    } else {
        $baseUrl = trim((string) ($tenant->tripay_base_url ?? ''));
        $apiKey = trim((string) ($tenant->tripay_api_key ?? ''));
        $merchantCode = trim((string) ($tenant->tripay_merchant_code ?? ''));
        $privateKey = trim((string) ($tenant->tripay_private_key ?? ''));
    }

    if ($baseUrl === '' || $apiKey === '' || $merchantCode === '' || $privateKey === '') {
        return null;
    }

    return [
        'base_url' => rtrim($baseUrl, '/').'/',
        'api_key' => $apiKey,
        'merchant_code' => $merchantCode,
        'private_key' => $privateKey,
        'gateway_mode' => $gatewayMode,
    ];
}

function recordTripayUsageLog(int $tenantId, string $merchantRef, array $attrs = []): void
{
    if ($tenantId < 1 || trim($merchantRef) === '') {
        return;
    }
    if (! class_exists(\App\Models\TripayUsageLog::class)) {
        return;
    }
    if (! \Illuminate\Support\Facades\Schema::hasTable('tripay_usage_logs')) {
        return;
    }

    $gatewayMode = (string) ($attrs['gateway_mode'] ?? 'owner');
    $defaults = [
        'tenant_id' => $tenantId,
        'merchant_ref' => $merchantRef,
        'tripay_reference' => $attrs['tripay_reference'] ?? null,
        'type' => $attrs['type'] ?? null,
        'method' => $attrs['method'] ?? null,
        'status' => $attrs['status'] ?? null,
        'amount' => isset($attrs['amount']) && is_numeric($attrs['amount']) ? (int) $attrs['amount'] : 0,
        'gateway_mode' => $gatewayMode,
        'paid_at' => $attrs['paid_at'] ?? null,
        'payload' => $attrs['payload'] ?? null,
    ];

    \App\Models\TripayUsageLog::query()->updateOrCreate(
        ['merchant_ref' => $merchantRef, 'gateway_mode' => $gatewayMode],
        $defaults
    );
}

function resolveTenantIdFromTagihanId(int $tagihanId): int
{
    if ($tagihanId < 1) {
        return 1;
    }
    try {
        $tenantId = DBFacade::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->where('tagihans.id', $tagihanId)
            ->value('pelanggans.tenant_id');

        return (int) ($tenantId ?? 1);
    } catch (\Throwable $e) {
        return 1;
    }
}

function resolveTenantIdFromNoLayanan(string $noLayanan): int
{
    $noLayanan = trim($noLayanan);
    if ($noLayanan === '') {
        return 1;
    }
    [$tidGuess, $nl] = parsePrefixedNoLayanan($noLayanan);
    if ($tidGuess > 0 && $nl !== '') {
        return $tidGuess;
    }
    try {
        $tenantId = DBFacade::table('pelanggans')->where('no_layanan', $noLayanan)->value('tenant_id');

        return (int) ($tenantId ?? 1);
    } catch (\Throwable $e) {
        return 1;
    }
}

function resolveTenantIdFromTripayReference(string $reference): int
{
    $reference = trim($reference);
    if ($reference === '') {
        return 1;
    }
    try {
        $tenantId = DBFacade::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->where('tagihans.tripay_reference', $reference)
            ->value('pelanggans.tenant_id');

        return (int) ($tenantId ?? 1);
    } catch (\Throwable $e) {
        return 1;
    }
}

function resolveTenantIdFromInvoiceRef(string $invoiceId): int
{
    $invoiceId = trim($invoiceId);
    if ($invoiceId === '') {
        return 1;
    }
    try {
        $tenantId = DBFacade::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->where('tagihans.no_tagihan', $invoiceId)
            ->value('pelanggans.tenant_id');

        return (int) ($tenantId ?? 1);
    } catch (\Throwable $e) {
        return 1;
    }
}

function resolveTenantIdFromTopupRef(string $topupNo): int
{
    $topupNo = trim($topupNo);
    if ($topupNo === '') {
        return 1;
    }
    try {
        $tenantId = DBFacade::table('topups')
            ->leftJoin('pelanggans', 'topups.pelanggan_id', '=', 'pelanggans.id')
            ->where('topups.no_topup', $topupNo)
            ->value('pelanggans.tenant_id');

        return (int) ($tenantId ?? 1);
    } catch (\Throwable $e) {
        return 1;
    }
}

function getWaGatewayActive()
{
    $provider = config('whatsapp.provider');
    if ($provider === 'ivosight') {
        $settingWeb = getSettingWeb();
        $isActive = ($settingWeb->is_wa_broadcast_active ?? 'Yes') === 'Yes' ? 'Yes' : 'No';

        return (object) [
            'provider' => 'ivosight',
            'is_aktif' => $isActive,
            'api_key' => config('whatsapp.ivosight.api_key'),
            'is_wa_billing_active' => ($settingWeb->is_wa_billing_active ?? 'Yes') === 'Yes' ? 'Yes' : 'No',
            'is_wa_payment_active' => ($settingWeb->is_wa_payment_active ?? 'Yes') === 'Yes' ? 'Yes' : 'No',
            'is_wa_welcome_active' => ($settingWeb->is_wa_welcome_active ?? 'Yes') === 'Yes' ? 'Yes' : 'No',
        ];
    }

    return (object) [
        'provider' => $provider,
        'is_aktif' => 'No',
        'api_key' => null,
        'is_wa_billing_active' => 'No',
        'is_wa_payment_active' => 'No',
        'is_wa_welcome_active' => 'No',
    ];
}
