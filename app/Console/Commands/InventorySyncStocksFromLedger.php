<?php

namespace App\Console\Commands;

use App\Services\InventoryStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InventorySyncStocksFromLedger extends Command
{
    protected $signature = 'inventory:sync-stocks-ledger {--dry-run}';

    protected $description = 'Sinkronisasi qty barang_owner_stocks berdasarkan ledger transaksi_details (jenis_transaksi in/out)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $ledger = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->select(
                'td.barang_id',
                'td.owner_type',
                'td.owner_user_id',
                DB::raw("SUM(CASE WHEN t.jenis_transaksi = 'in' THEN td.jumlah ELSE -td.jumlah END) as qty_ledger")
            )
            ->groupBy('td.barang_id', 'td.owner_type', 'td.owner_user_id')
            ->get();

        $count = $ledger->count();
        $this->info("Ledger rows: {$count}");
        if ($count < 1) {
            return self::SUCCESS;
        }

        $updated = 0;
        $checked = 0;

        foreach ($ledger as $row) {
            $checked++;
            $barangId = (int) $row->barang_id;
            $ownerType = (string) $row->owner_type;
            $ownerUserId = $row->owner_user_id !== null ? (int) $row->owner_user_id : null;
            $qtyLedger = max(0, (int) ($row->qty_ledger ?? 0));

            $existing = DB::table('barang_owner_stocks')
                ->where('barang_id', $barangId)
                ->where('owner_type', $ownerType)
                ->where('owner_user_id', $ownerUserId)
                ->select('id', 'qty')
                ->first();

            $qtyNow = (int) ($existing->qty ?? 0);
            if ($qtyNow === $qtyLedger) {
                continue;
            }

            $updated++;
            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($existing, $barangId, $ownerType, $ownerUserId, $qtyLedger) {
                if ($existing) {
                    DB::table('barang_owner_stocks')
                        ->where('id', (int) $existing->id)
                        ->update([
                            'qty' => $qtyLedger,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('barang_owner_stocks')->insert([
                        'barang_id' => $barangId,
                        'owner_type' => $ownerType,
                        'owner_user_id' => $ownerUserId,
                        'qty' => $qtyLedger,
                        'hpp_unit' => 0,
                        'harga_jual_unit' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                InventoryStockService::repairOwnerStockKey($barangId, $ownerType, $ownerUserId);
                InventoryStockService::syncOfficeStockToBarang($barangId);
            });
        }

        $mode = $dryRun ? 'DRY RUN' : 'APPLIED';
        $this->info("Selesai ({$mode}): checked={$checked}, updated={$updated}");

        return self::SUCCESS;
    }
}

