<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvestorBackfillEarnings extends Command
{
    protected $signature = 'investor:backfill-earnings
        {--tagihan_id= : Proses satu tagihan berdasarkan ID}
        {--period= : Filter periode YYYY-MM}
        {--from_period= : Filter periode mulai YYYY-MM}
        {--to_period= : Filter periode sampai YYYY-MM}
        {--from_date= : Filter tanggal_bayar mulai (YYYY-MM-DD)}
        {--to_date= : Filter tanggal_bayar sampai (YYYY-MM-DD)}
        {--limit= : Batasi jumlah tagihan diproses}
        {--dry-run : Hanya tampilkan estimasi tanpa mengubah data}';

    protected $description = 'Backfill bagi hasil Investor/Mitra untuk tagihan yang sudah dibayar di periode lampau (idempotent via investor_earnings).';

    public function handle(): int
    {
        if (!Schema::hasTable('investor_share_rules') || !Schema::hasTable('investor_earnings')) {
            $this->error('Tabel investor belum tersedia. Pastikan migrasi sudah dijalankan.');
            return Command::FAILURE;
        }

        $paidStatuses = ['sudah bayar', 'paid', 'lunas'];
        $query = DB::table('tagihans')
            ->select('id', 'no_tagihan', 'periode', 'status_bayar', 'tanggal_bayar', 'updated_at')
            ->whereRaw("LOWER(TRIM(status_bayar)) IN ('" . implode("','", $paidStatuses) . "')");

        $tagihanId = $this->option('tagihan_id');
        if (!empty($tagihanId)) {
            $query->where('id', (int) $tagihanId);
        }

        $period = trim((string) $this->option('period'));
        if ($period !== '') {
            $query->where('periode', $period);
        } else {
            $fromPeriod = trim((string) $this->option('from_period'));
            $toPeriod = trim((string) $this->option('to_period'));
            if ($fromPeriod !== '') {
                $query->where('periode', '>=', $fromPeriod);
            }
            if ($toPeriod !== '') {
                $query->where('periode', '<=', $toPeriod);
            }
        }

        $fromDate = trim((string) $this->option('from_date'));
        $toDate = trim((string) $this->option('to_date'));
        if ($fromDate !== '') {
            $query->whereDate('tanggal_bayar', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $query->whereDate('tanggal_bayar', '<=', $toDate);
        }

        $limit = $this->option('limit');
        if (!empty($limit)) {
            $query->limit((int) $limit);
        }

        $total = (clone $query)->count();
        $this->info('Tagihan kandidat: ' . number_format($total));

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $sample = (clone $query)->orderBy('id')->limit(10)->get();
            foreach ($sample as $row) {
                $this->line('#' . $row->id . ' ' . ($row->no_tagihan ?? '-') . ' periode=' . ($row->periode ?? '-') . ' status=' . ($row->status_bayar ?? '-'));
            }
            $this->info('Dry-run selesai. Jalankan tanpa --dry-run untuk memproses backfill.');
            return Command::SUCCESS;
        }

        $processed = 0;
        $credited = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(200, function ($rows) use (&$processed, &$credited, &$skipped) {
            foreach ($rows as $row) {
                $processed++;
                $before = DB::table('investor_earnings')->where('tagihan_id', (int) $row->id)->count();
                try {
                    applyInvestorSharingForPaidTagihan((int) $row->id);
                } catch (\Throwable $e) {
                    $this->error('Gagal proses tagihan_id=' . (string) $row->id . ': ' . $e->getMessage());
                    continue;
                }
                $after = DB::table('investor_earnings')->where('tagihan_id', (int) $row->id)->count();
                if ($after > $before) {
                    $credited++;
                } else {
                    $skipped++;
                }
            }
        }, 'id');

        $this->info('Selesai. Diproses: ' . number_format($processed) . ' | Kredit baru: ' . number_format($credited) . ' | Skip (sudah ada / tidak match rule): ' . number_format($skipped));
        return Command::SUCCESS;
    }
}

