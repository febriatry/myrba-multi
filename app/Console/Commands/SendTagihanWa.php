<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendTagihanWa extends Command
{
    protected $signature = 'tagihan:send-wa {--limit=200 : Batas data per eksekusi}';

    protected $description = 'Mengirim notifikasi tagihan WA via gateway template Ivosight';

    public function handle(): int
    {
        $waGateway = getWaGatewayActive();
        if ($waGateway->is_aktif !== 'Yes' || $waGateway->is_wa_billing_active !== 'Yes') {
            $this->info('Pengiriman WA tagihan dinonaktifkan di pengaturan.');
            return Command::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $since = now()->subDays(45);
        $rows = DB::table('tagihans')
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
            ->where('tagihans.is_send', 'No')
            ->where('tagihans.status_bayar', 'Belum Bayar')
            ->where('tagihans.tanggal_create_tagihan', '>=', $since)
            ->where(function ($q) {
                $q->whereNull('tagihans.retry')->orWhere('tagihans.retry', '<=', 2);
            })
            ->orderBy('tagihans.id')
            ->limit($limit)
            ->get();

        $this->info('Target tagihan WA: ' . $rows->count());

        $ok = 0;
        $failed = 0;

        foreach ($rows as $row) {
            // Re-check status to prevent race condition if payment happened during execution
            $freshStatus = DB::table('tagihans')->where('id', $row->id_tagihan)->value('status_bayar');
            if ($freshStatus !== 'Belum Bayar') {
                continue;
            }

            if ($row->kirim_tagihan_wa !== 'Yes') {
                continue;
            }
            if (empty($row->no_wa)) {
                DB::table('tagihans')->where('id', $row->id_tagihan)->increment('retry');
                $failed++;
                continue;
            }

            try {
                $response = sendNotifWa('', $row, 'billing_reminder', $row->no_wa);
                if (isset($response->status) && ($response->status === true || $response->status === 'true')) {
                    DB::table('tagihans')
                        ->where('id', $row->id_tagihan)
                        ->update([
                            'is_send' => 'Yes',
                            'tanggal_kirim_notif_wa' => now(),
                            'updated_at' => now(),
                        ]);
                    $ok++;
                } else {
                    DB::table('tagihans')->where('id', $row->id_tagihan)->increment('retry');
                    $failed++;
                    Log::warning('Kirim tagihan WA gagal', [
                        'tagihan_id' => $row->id_tagihan,
                        'no_wa' => $row->no_wa,
                        'message' => $response->message ?? 'Unknown error',
                    ]);
                }
            } catch (\Throwable $e) {
                DB::table('tagihans')->where('id', $row->id_tagihan)->increment('retry');
                $failed++;
                Log::error('Exception kirim tagihan WA', [
                    'tagihan_id' => $row->id_tagihan,
                    'no_wa' => $row->no_wa,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Selesai kirim WA tagihan. Berhasil={$ok}, Gagal={$failed}");
        return Command::SUCCESS;
    }
}
