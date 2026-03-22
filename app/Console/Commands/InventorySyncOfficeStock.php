<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InventorySyncOfficeStock extends Command
{
    protected $signature = 'inventory:sync-office-stock {--dry-run} {--chunk=500}';

    protected $description = 'Sinkronisasi stok kantor dari kolom barang.stock ke tabel barang_owner_stocks (owner_type=office)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(50, (int) $this->option('chunk'));

        $total = (int) DB::table('barang')->count();
        $this->info("Scan {$total} barang...");

        $updated = 0;
        $created = 0;
        $skipped = 0;

        DB::table('barang')
            ->select('id', 'stock')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($dryRun, &$updated, &$created, &$skipped) {
                foreach ($rows as $row) {
                    $barangId = (int) $row->id;
                    $stock = max(0, (int) ($row->stock ?? 0));

                    $existing = DB::table('barang_owner_stocks')
                        ->where('barang_id', $barangId)
                        ->where('owner_type', 'office')
                        ->whereNull('owner_user_id')
                        ->select('id', 'qty')
                        ->first();

                    if ($existing) {
                        $oldQty = (int) ($existing->qty ?? 0);
                        if ($oldQty === $stock) {
                            $skipped++;
                            continue;
                        }
                        $updated++;
                        if (!$dryRun) {
                            DB::table('barang_owner_stocks')
                                ->where('id', (int) $existing->id)
                                ->update([
                                    'qty' => $stock,
                                    'updated_at' => now(),
                                ]);
                            DB::table('barang_owner_stocks')
                                ->where('barang_id', $barangId)
                                ->where('owner_type', 'office')
                                ->whereNull('owner_user_id')
                                ->where('id', '!=', (int) $existing->id)
                                ->delete();
                        }
                        continue;
                    }

                    $created++;
                    if (!$dryRun) {
                        DB::table('barang_owner_stocks')->insert([
                            'barang_id' => $barangId,
                            'owner_type' => 'office',
                            'owner_user_id' => null,
                            'qty' => $stock,
                            'hpp_unit' => 0,
                            'harga_jual_unit' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        $mode = $dryRun ? 'DRY RUN' : 'APPLIED';
        $this->info("Selesai ({$mode}): created={$created}, updated={$updated}, skipped={$skipped}");

        return self::SUCCESS;
    }
}
