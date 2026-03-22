<?php

namespace App\Console\Commands;

use App\Services\InventoryStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InventoryRepairOwnerStocks extends Command
{
    protected $signature = 'inventory:repair-owner-stocks {--dry-run}';

    protected $description = 'Perbaiki duplikasi barang_owner_stocks (merge qty/pricing) dan normalisasi office owner_user_id=0 menjadi NULL';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun) {
            DB::table('barang_owner_stocks')
                ->where('owner_type', 'office')
                ->where('owner_user_id', 0)
                ->update([
                    'owner_user_id' => null,
                    'updated_at' => now(),
                ]);
        }

        $dups = DB::table('barang_owner_stocks')
            ->select('barang_id', 'owner_type', 'owner_user_id', DB::raw('COUNT(1) as c'))
            ->groupBy('barang_id', 'owner_type', 'owner_user_id')
            ->having('c', '>', 1)
            ->get();

        $total = $dups->count();
        $this->info('Duplikasi ditemukan: ' . $total);
        if ($total < 1) {
            return self::SUCCESS;
        }

        $fixed = 0;
        foreach ($dups as $d) {
            $barangId = (int) $d->barang_id;
            $ownerType = (string) $d->owner_type;
            $ownerUserId = $d->owner_user_id !== null ? (int) $d->owner_user_id : null;

            if ($dryRun) {
                $fixed++;
                continue;
            }

            DB::transaction(function () use ($barangId, $ownerType, $ownerUserId, &$fixed) {
                InventoryStockService::repairOwnerStockKey($barangId, $ownerType, $ownerUserId);
                InventoryStockService::syncOfficeStockToBarang($barangId);
                $fixed++;
            });
        }

        $mode = $dryRun ? 'DRY RUN' : 'APPLIED';
        $this->info("Selesai ({$mode}): fixed={$fixed}");

        return self::SUCCESS;
    }
}

