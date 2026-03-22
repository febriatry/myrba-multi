<?php

namespace Database\Seeders;

use App\Models\WaTemplate;
use App\Models\WaTemplateMapping;
use Illuminate\Database\Seeder;

class WaTemplateMappingSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'daftar' => [
                ['param_index' => 1, 'source_key' => 'nama_perusahaan'],
                ['param_index' => 2, 'source_key' => 'pelanggan.nama'],
                ['param_index' => 3, 'source_key' => 'pelanggan.no_layanan'],
                ['param_index' => 4, 'source_key' => 'setting.no_wa'],
            ],
            'tagihan' => [
                ['param_index' => 1, 'source_key' => 'pelanggan.nama'],
                ['param_index' => 2, 'source_key' => 'tagihan.no_tagihan'],
                ['param_index' => 3, 'source_key' => 'tagihan.total_bayar'],
                ['param_index' => 4, 'source_key' => 'tagihan.periode'],
            ],
            'bayar' => [
                ['param_index' => 1, 'source_key' => 'pelanggan.nama'],
                ['param_index' => 2, 'source_key' => 'tagihan.no_tagihan'],
                ['param_index' => 3, 'source_key' => 'tagihan.metode_bayar'],
                ['param_index' => 4, 'source_key' => 'tagihan.total_bayar'],
            ],
            'invoice' => [
                ['param_index' => 1, 'source_key' => 'pelanggan.nama'],
                ['param_index' => 2, 'source_key' => 'tagihan.no_tagihan'],
                ['param_index' => 3, 'source_key' => 'tagihan.total_bayar'],
                ['param_index' => 4, 'source_key' => 'tagihan.id'],
            ],
        ];

        foreach ($definitions as $messageType => $rows) {
            $template = $this->resolveTemplate($messageType);
            if (!$template) {
                continue;
            }

            foreach ($rows as $row) {
                WaTemplateMapping::updateOrCreate(
                    [
                        'template_id' => (string) $template->template_id,
                        'message_type' => $messageType,
                        'component_type' => 'body',
                        'component_index' => 0,
                        'param_index' => $row['param_index'],
                    ],
                    [
                        'component_sub_type' => null,
                        'source_key' => $row['source_key'],
                        'parameter_type' => 'text',
                        'default_value' => null,
                        'is_required' => 'Yes',
                        'notes' => 'Generated default mapping',
                    ]
                );
            }
        }
    }

    private function resolveTemplate(string $messageType): ?WaTemplate
    {
        $keywords = [
            'daftar' => ['daftar', 'welcome', 'registr'],
            'tagihan' => ['tagih', 'bill', 'invoice_due'],
            'bayar' => ['bayar', 'payment', 'paid'],
            'invoice' => ['invoice'],
        ];

        $query = WaTemplate::query();
        if (!empty($keywords[$messageType])) {
            $query->where(function ($q) use ($keywords, $messageType) {
                foreach ($keywords[$messageType] as $keyword) {
                    $q->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhere('template_id', 'like', '%' . $keyword . '%');
                }
            });
        }

        $template = $query->orderByDesc('updated_at')->first();
        if ($template) {
            return $template;
        }

        return WaTemplate::query()->orderByDesc('updated_at')->first();
    }
}
