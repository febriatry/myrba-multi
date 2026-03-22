<?php

namespace App\Console\Commands;

use App\Models\WaTemplate;
use App\Services\WhatsApp\IvosightGateway;
use Illuminate\Console\Command;

class SyncIvosightTemplates extends Command
{
    protected $signature = 'wa:sync-templates';

    protected $description = 'Sinkronisasi template WA dari Ivosight';

    public function handle(): int
    {
        $gateway = new IvosightGateway();
        $templates = $gateway->fetchTemplates();

        if (empty($templates)) {
            $this->error('Template tidak ditemukan atau endpoint tidak mengembalikan data.');
            return Command::FAILURE;
        }

        $synced = 0;
        foreach ($templates as $template) {
            if (!is_array($template)) {
                continue;
            }

            $templateId = (string) ($template['id'] ?? $template['template_id'] ?? $template['template_name'] ?? $template['name'] ?? '');
            if ($templateId === '') {
                continue;
            }

            WaTemplate::updateOrCreate(
                ['template_id' => $templateId],
                [
                    'name' => (string) ($template['label'] ?? $template['template_name'] ?? $template['name'] ?? $templateId),
                    'language' => isset($template['language_id']) ? (string) $template['language_id'] : (isset($template['language']) ? (string) $template['language'] : (isset($template['lang']) ? (string) $template['lang'] : null)),
                    'category' => isset($template['category_id']) ? (string) $template['category_id'] : (isset($template['category']) ? (string) $template['category'] : null),
                    'status' => isset($template['status']) ? (string) $template['status'] : null,
                    'components' => isset($template['components']) && is_array($template['components']) ? $template['components'] : null,
                    'payload' => $template,
                    'synced_at' => now(),
                ]
            );
            $synced++;
        }

        $this->info("Sinkronisasi selesai. Template tersinkron: {$synced}");
        return Command::SUCCESS;
    }
}
