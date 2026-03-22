<?php

namespace App\Console\Commands;

use App\Models\WaTemplate;
use App\Models\WaTemplateMapping;
use App\Support\WaMessageTrigger;
use Illuminate\Console\Command;

class WaAutofixTemplateMappings extends Command
{
    protected $signature = 'wa:autofix-template-mappings {--dry-run : Simulasi tanpa menyimpan perubahan}';

    protected $description = 'Auto-fix mapping template WA agar mengarah ke template aktif yang valid';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $types = [
            WaMessageTrigger::BILLING_REMINDER,
            WaMessageTrigger::PAYMENT_RECEIPT,
            WaMessageTrigger::WELCOME_REGISTRATION,
            WaMessageTrigger::INVOICE_LINK,
            WaMessageTrigger::BROADCAST,
        ];

        $fixed = 0;
        foreach ($types as $type) {
            $targetTemplate = $this->resolveTargetTemplate($type);
            if (!$targetTemplate) {
                $this->warn("{$type}: tidak ditemukan template target yang valid.");
                continue;
            }

            $candidates = array_map('strtolower', WaMessageTrigger::candidates($type));
            $rows = WaTemplateMapping::query()
                ->where(function ($query) use ($candidates) {
                    foreach ($candidates as $candidate) {
                        $query->orWhereRaw('LOWER(TRIM(message_type)) = ?', [$candidate]);
                    }
                })
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                $this->line("{$type}: tidak ada mapping yang bisa diperbaiki.");
                continue;
            }

            $affected = 0;
            $deleteIds = [];
            foreach ($rows as $row) {
                $normalizedType = WaMessageTrigger::normalize((string) $row->message_type);
                $payload = [
                    'component_sub_type' => $row->component_sub_type,
                    'source_key' => $row->source_key,
                    'parameter_type' => $row->parameter_type,
                    'default_value' => $row->default_value,
                    'is_required' => $row->is_required,
                    'notes' => $row->notes,
                ];

                if (!$dryRun) {
                    WaTemplateMapping::updateOrCreate(
                        [
                            'template_id' => $targetTemplate->template_id,
                            'message_type' => $normalizedType,
                            'component_type' => $row->component_type,
                            'component_index' => (int) ($row->component_index ?? 0),
                            'param_index' => (int) $row->param_index,
                        ],
                        $payload
                    );
                }

                if ((string) $row->template_id !== (string) $targetTemplate->template_id) {
                    $deleteIds[] = (int) $row->id;
                }
                $affected++;
            }

            if (!$dryRun && !empty($deleteIds)) {
                WaTemplateMapping::query()->whereIn('id', array_unique($deleteIds))->delete();
            }

            $fixed += $affected;
            $this->info("{$type}: target={$targetTemplate->template_id} ({$targetTemplate->name}), affected={$affected}");
        }

        $suffix = $dryRun ? ' [DRY RUN]' : '';
        $this->info("Selesai auto-fix mapping. Total baris diproses: {$fixed}{$suffix}");
        return Command::SUCCESS;
    }

    private function resolveTargetTemplate(string $type): ?WaTemplate
    {
        $configMap = [
            WaMessageTrigger::BILLING_REMINDER => 'whatsapp.ivosight.template_id_billing_reminder',
            WaMessageTrigger::PAYMENT_RECEIPT => 'whatsapp.ivosight.template_id_payment_receipt',
            WaMessageTrigger::WELCOME_REGISTRATION => 'whatsapp.ivosight.template_id_welcome_registration',
            WaMessageTrigger::INVOICE_LINK => 'whatsapp.ivosight.template_id_invoice_link',
            WaMessageTrigger::BROADCAST => 'whatsapp.ivosight.template_id_broadcast',
        ];

        $configKey = $configMap[$type] ?? null;
        $forcedId = $configKey ? trim((string) config($configKey)) : '';
        if ($forcedId !== '') {
            $forcedTemplate = WaTemplate::query()->where('template_id', $forcedId)->first();
            if ($forcedTemplate) {
                return $forcedTemplate;
            }
        }

        $candidates = array_map('strtolower', WaMessageTrigger::candidates($type));
        $mappedTemplateIds = WaTemplateMapping::query()
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $candidate) {
                    $query->orWhereRaw('LOWER(TRIM(message_type)) = ?', [$candidate]);
                }
            })
            ->orderByDesc('updated_at')
            ->pluck('template_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($mappedTemplateIds as $templateId) {
            $template = WaTemplate::query()->where('template_id', (string) $templateId)->first();
            if ($template) {
                return $template;
            }
        }

        $keywords = $this->typeKeywords($type);
        foreach ($keywords as $keyword) {
            $template = WaTemplate::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($keyword) . '%'])
                ->orderByRaw("CASE WHEN LOWER(status) LIKE '%approved%' THEN 0 ELSE 1 END")
                ->orderByDesc('synced_at')
                ->first();
            if ($template) {
                return $template;
            }
        }

        return WaTemplate::query()
            ->orderByRaw("CASE WHEN LOWER(status) LIKE '%approved%' THEN 0 ELSE 1 END")
            ->orderByDesc('synced_at')
            ->first();
    }

    private function typeKeywords(string $type): array
    {
        return match ($type) {
            WaMessageTrigger::BILLING_REMINDER => ['tagihan', 'billing', 'reminder'],
            WaMessageTrigger::PAYMENT_RECEIPT => ['bukti', 'pembayaran', 'payment', 'receipt'],
            WaMessageTrigger::WELCOME_REGISTRATION => ['welcome', 'daftar', 'pendaftaran'],
            WaMessageTrigger::INVOICE_LINK => ['invoice'],
            WaMessageTrigger::BROADCAST => ['broadcast', 'blast'],
            default => [$type],
        };
    }
}
