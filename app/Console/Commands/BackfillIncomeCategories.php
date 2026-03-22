<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillIncomeCategories extends Command
{
    protected $signature = 'income:backfill-categories {--dry-run : Only show what would change}';

    protected $description = 'Backfill pemasukan categories per area for existing records based on related tagihan/pelanggan';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        $this->info('Starting backfill of pemasukan categories by coverage area');

        $rows = DB::table('pemasukans')
            ->leftJoin('tagihans', 'pemasukans.referense_id', '=', 'tagihans.id')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->select(
                'pemasukans.id as pemasukan_id',
                'pemasukans.category_pemasukan_id',
                'pemasukans.keterangan',
                'pemasukans.nominal',
                'tagihans.id as tagihan_id',
                'pelanggans.id as pelanggan_id',
                'area_coverages.nama as area_nama',
                'area_coverages.kode_area as area_kode'
            )
            ->orderBy('pemasukans.id', 'asc')
            ->get();

        foreach ($rows as $row) {
            if (!$row->pelanggan_id) {
                $skipped++;
                continue;
            }
            $newCategoryId = getInternetIncomeCategoryIdForPelanggan($row->pelanggan_id);
            if ($dry) {
                $this->line("Would update pemasukan #{$row->pemasukan_id} to category_id={$newCategoryId}");
                $updated++;
                continue;
            }
            DB::table('pemasukans')
                ->where('id', $row->pemasukan_id)
                ->update(['category_pemasukan_id' => $newCategoryId]);
            $updated++;
        }

        $this->info("Backfill done. Updated: {$updated}, Skipped (no pelanggan/tagihan): {$skipped}");
        return Command::SUCCESS;
    }
}
