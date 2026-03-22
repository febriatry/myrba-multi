<?php

namespace App\Console\Commands;

use App\Models\WaTemplateMapping;
use App\Models\WaTemplate;
use App\Support\WaMessageTrigger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WaDebugTemplateMapping extends Command
{
    protected $signature = 'wa:debug-template-mapping {type=tagihan} {--tagihan_id=}';

    protected $description = 'Debug hasil builder mapping template WA per message_type';

    public function handle(): int
    {
        $type = WaMessageTrigger::normalize((string) $this->argument('type'));
        $tagihanId = $this->option('tagihan_id');
        $candidates = array_map('strtolower', WaMessageTrigger::candidates($type));

        $candidateMappings = WaTemplateMapping::query()
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $candidate) {
                    $query->orWhereRaw('LOWER(TRIM(message_type)) = ?', [$candidate]);
                }
            })
            ->orderByDesc('updated_at')
            ->get();

        if ($candidateMappings->isEmpty()) {
            $this->error("Mapping tidak ditemukan untuk message_type={$type}");
            return Command::FAILURE;
        }
        $selectedType = null;
        foreach ($candidates as $candidate) {
            $exists = $candidateMappings->first(function ($row) use ($candidate) {
                return strtolower(trim((string) $row->message_type)) === $candidate;
            });
            if ($exists) {
                $selectedType = $candidate;
                break;
            }
        }
        $selectedType = $selectedType ?? strtolower(trim((string) $candidateMappings->first()->message_type));
        $selectedTemplateId = resolvePreferredTemplateIdForTrigger($type, $selectedType, $candidateMappings);
        $waTemplate = WaTemplate::query()->where('template_id', $selectedTemplateId)->first();
        $templateReference = trim((string) ($waTemplate->name ?? ''));
        $templateReference = $templateReference !== '' ? $templateReference : $selectedTemplateId;

        $mappings = WaTemplateMapping::query()
            ->where('template_id', $selectedTemplateId)
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $candidate) {
                    $query->orWhereRaw('LOWER(TRIM(message_type)) = ?', [$candidate]);
                }
            })
            ->orderBy('component_type')
            ->orderBy('component_index')
            ->orderBy('param_index')
            ->get();

        $query = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->select(
                'tagihans.*',
                'tagihans.id as id_tagihan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.no_layanan',
                'pelanggans.kirim_tagihan_wa',
                'pelanggans.jatuh_tempo'
            );

        if (!empty($tagihanId)) {
            $query->where('tagihans.id', (int) $tagihanId);
        } else {
            $query->where('tagihans.status_bayar', 'Belum Bayar');
        }

        $row = $query->orderByDesc('tagihans.id')->first();
        if (!$row) {
            $this->error('Data contoh tagihan tidak ditemukan.');
            return Command::FAILURE;
        }

        $report = buildWaTemplateComponentsReport($mappings, $row, $type);
        $this->info("message_type: {$type}");
        $this->line('resolved_message_type: ' . $selectedType);
        $this->line('template_id: ' . $selectedTemplateId);
        $this->line('template_ref_for_gateway: ' . $templateReference);
        $this->line('tagihan_id: ' . (string) $row->id_tagihan);
        $this->line('no_wa: ' . (string) ($row->no_wa ?? '-'));
        $this->line('components_count: ' . count($report['components']));

        if (!empty($report['missing_required'])) {
            $this->warn('missing_required:');
            foreach ($report['missing_required'] as $missing) {
                $this->line('- ' . $missing);
            }
        } else {
            $this->info('missing_required: none');
        }

        $this->line('components_json:');
        $this->line(json_encode($report['components'], JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
