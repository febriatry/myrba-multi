<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryStockService
{
    public static function getOwnerQty(int $barangId, string $ownerType, ?int $ownerUserId = null): int
    {
        return (int) (DB::table('barang_owner_stocks')
            ->where('barang_id', $barangId)
            ->where('owner_type', $ownerType)
            ->where('owner_user_id', $ownerUserId)
            ->sum('qty') ?? 0);
    }

    public static function getOwnerPricing(int $barangId, string $ownerType, ?int $ownerUserId = null): array
    {
        $row = DB::table('barang_owner_stocks')
            ->where('barang_id', $barangId)
            ->where('owner_type', $ownerType)
            ->where('owner_user_id', $ownerUserId)
            ->select('hpp_unit', 'harga_jual_unit')
            ->first();
        return [
            'hpp_unit' => (int) ($row->hpp_unit ?? 0),
            'harga_jual_unit' => (int) ($row->harga_jual_unit ?? 0),
        ];
    }

    public static function repairOwnerStockKey(int $barangId, string $ownerType, ?int $ownerUserId = null): void
    {
        $rows = DB::table('barang_owner_stocks')
            ->where('barang_id', $barangId)
            ->where('owner_type', $ownerType)
            ->where('owner_user_id', $ownerUserId)
            ->lockForUpdate()
            ->get(['id', 'qty', 'hpp_unit', 'harga_jual_unit']);

        if ($rows->count() < 2) {
            return;
        }

        $keeperId = (int) $rows->min('id');
        $totalQty = 0;
        $totalHppValue = 0;
        $totalPriceValue = 0;
        foreach ($rows as $r) {
            $q = max(0, (int) ($r->qty ?? 0));
            $totalQty += $q;
            $totalHppValue += $q * max(0, (int) ($r->hpp_unit ?? 0));
            $totalPriceValue += $q * max(0, (int) ($r->harga_jual_unit ?? 0));
        }
        $hppUnit = $totalQty > 0 ? (int) floor($totalHppValue / $totalQty) : 0;
        $hargaJualUnit = $totalQty > 0 ? (int) floor($totalPriceValue / $totalQty) : 0;

        DB::table('barang_owner_stocks')
            ->where('id', $keeperId)
            ->update([
                'qty' => $totalQty,
                'hpp_unit' => $hppUnit,
                'harga_jual_unit' => $hargaJualUnit,
                'updated_at' => now(),
            ]);

        DB::table('barang_owner_stocks')
            ->where('barang_id', $barangId)
            ->where('owner_type', $ownerType)
            ->where('owner_user_id', $ownerUserId)
            ->where('id', '!=', $keeperId)
            ->delete();
    }

    private static function lockAndGetOwnerRow(int $barangId, string $ownerType, ?int $ownerUserId): ?object
    {
        $rows = DB::table('barang_owner_stocks')
            ->where('barang_id', $barangId)
            ->where('owner_type', $ownerType)
            ->where('owner_user_id', $ownerUserId)
            ->lockForUpdate()
            ->get(['id', 'qty', 'hpp_unit', 'harga_jual_unit']);

        if ($rows->isEmpty()) {
            return null;
        }

        if ($rows->count() > 1) {
            self::repairOwnerStockKey($barangId, $ownerType, $ownerUserId);
            return DB::table('barang_owner_stocks')
                ->where('barang_id', $barangId)
                ->where('owner_type', $ownerType)
                ->where('owner_user_id', $ownerUserId)
                ->lockForUpdate()
                ->select('id', 'qty', 'hpp_unit', 'harga_jual_unit')
                ->first();
        }

        return $rows->first();
    }

    public static function ensureOwnerPricing(int $barangId, string $ownerType, ?int $ownerUserId, int $hppUnit, int $hargaJualUnit): void
    {
        $hppUnit = max(0, (int) $hppUnit);
        $hargaJualUnit = max(0, (int) $hargaJualUnit);
        $existing = self::lockAndGetOwnerRow($barangId, $ownerType, $ownerUserId);
        if ($existing) {
            DB::table('barang_owner_stocks')->where('id', (int) $existing->id)->update([
                'hpp_unit' => $hppUnit > 0 ? $hppUnit : (int) ($existing->hpp_unit ?? 0),
                'harga_jual_unit' => $hargaJualUnit > 0 ? $hargaJualUnit : (int) ($existing->harga_jual_unit ?? 0),
                'updated_at' => now(),
            ]);
            return;
        }
        DB::table('barang_owner_stocks')->insert([
            'barang_id' => $barangId,
            'owner_type' => $ownerType,
            'owner_user_id' => $ownerUserId,
            'qty' => 0,
            'hpp_unit' => $hppUnit,
            'harga_jual_unit' => $hargaJualUnit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function increase(int $barangId, string $ownerType, ?int $ownerUserId, int $qty): void
    {
        self::increaseWithPricing($barangId, $ownerType, $ownerUserId, $qty, null, null);
    }

    public static function increaseWithPricing(int $barangId, string $ownerType, ?int $ownerUserId, int $qty, ?int $hppUnit, ?int $hargaJualUnit): void
    {
        $qty = (int) $qty;
        if ($qty < 1) {
            return;
        }
        $hppUnit = $hppUnit !== null ? max(0, (int) $hppUnit) : null;
        $hargaJualUnit = $hargaJualUnit !== null ? max(0, (int) $hargaJualUnit) : null;
        $existing = self::lockAndGetOwnerRow($barangId, $ownerType, $ownerUserId);
        if ($existing) {
            $newQty = (int) $existing->qty + $qty;
            $update = [
                'qty' => $newQty,
                'updated_at' => now(),
            ];
            if ($hppUnit !== null) {
                $oldQty = max(0, (int) $existing->qty);
                $oldHpp = max(0, (int) ($existing->hpp_unit ?? 0));
                $total = ($oldQty * $oldHpp) + ($qty * $hppUnit);
                $update['hpp_unit'] = $newQty > 0 ? (int) floor($total / $newQty) : 0;
            }
            if ($hargaJualUnit !== null) {
                $oldQty = max(0, (int) $existing->qty);
                $oldPrice = max(0, (int) ($existing->harga_jual_unit ?? 0));
                $total = ($oldQty * $oldPrice) + ($qty * $hargaJualUnit);
                $update['harga_jual_unit'] = $newQty > 0 ? (int) floor($total / $newQty) : 0;
            }
            DB::table('barang_owner_stocks')->where('id', (int) $existing->id)->update($update);
        } else {
            DB::table('barang_owner_stocks')->insert([
                'barang_id' => $barangId,
                'owner_type' => $ownerType,
                'owner_user_id' => $ownerUserId,
                'qty' => $qty,
                'hpp_unit' => $hppUnit !== null ? $hppUnit : 0,
                'harga_jual_unit' => $hargaJualUnit !== null ? $hargaJualUnit : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        self::syncOfficeStockToBarang($barangId);
    }

    public static function decrease(int $barangId, string $ownerType, ?int $ownerUserId, int $qty): bool
    {
        $qty = (int) $qty;
        if ($qty < 1) {
            return true;
        }
        $existing = self::lockAndGetOwnerRow($barangId, $ownerType, $ownerUserId);
        if (!$existing || (int) $existing->qty < $qty) {
            return false;
        }
        DB::table('barang_owner_stocks')->where('id', (int) $existing->id)->update([
            'qty' => (int) $existing->qty - $qty,
            'updated_at' => now(),
        ]);
        self::syncOfficeStockToBarang($barangId);
        return true;
    }

    public static function syncOfficeStockToBarang(int $barangId): void
    {
        $officeQty = self::getOwnerQty($barangId, 'office', null);
        DB::table('barang')->where('id', $barangId)->update([
            'stock' => $officeQty,
            'updated_at' => now(),
        ]);
    }

    public static function ownerLabel(string $ownerType, ?string $ownerName = null): string
    {
        if ($ownerType === 'investor') {
            return 'Investor: ' . (($ownerName && trim($ownerName) !== '') ? $ownerName : '-');
        }
        return 'Kantor';
    }
}
