<?php

namespace App\Http\Controllers;

use App\Models\WaMessageStatusLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IvosightWebhookController extends Controller
{
    public function verify(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        $statuses = $payload['statuses'] ?? [];

        if (is_array($statuses)) {
            foreach ($statuses as $statusRow) {
                if (!is_array($statusRow)) {
                    continue;
                }

                $messageId = $statusRow['id'] ?? null;
                $status = $statusRow['status'] ?? null;

                if (!$messageId || !$status) {
                    continue;
                }

                $timestamp = $statusRow['timestamp'] ?? null;
                $statusAt = $timestamp ? Carbon::createFromTimestamp((int) $timestamp) : now();

                $tenantId = null;
                $existingTenant = WaMessageStatusLog::query()
                    ->where('message_id', (string) $messageId)
                    ->whereNotNull('tenant_id')
                    ->orderByDesc('id')
                    ->value('tenant_id');
                if ($existingTenant !== null) {
                    $tenantId = (int) $existingTenant;
                }

                WaMessageStatusLog::updateOrCreate(
                    [
                        'message_id' => (string) $messageId,
                        'status' => (string) $status,
                        'status_at' => $statusAt,
                    ],
                    [
                        'tenant_id' => $tenantId ?? 1,
                        'recipient_id' => isset($statusRow['recipient_id']) ? (string) $statusRow['recipient_id'] : null,
                        'type' => isset($statusRow['type']) ? (string) $statusRow['type'] : null,
                        'provider' => 'ivosight',
                        'cost_units' => 1,
                        'errors' => isset($statusRow['errors']) && is_array($statusRow['errors']) ? $statusRow['errors'] : null,
                        'payload' => $statusRow,
                    ]
                );
            }
        }

        Log::info('Ivosight Webhook', ['payload' => $payload]);
        return response()->json(['status' => 'received']);
    }
}
