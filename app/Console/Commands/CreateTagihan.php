<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateTagihan extends Command
{
    protected $signature = 'tagihan:create {--dry-run}';

    protected $description = 'Create monthly invoices (tagihans) based on due date rules (H-10), for current and next period.';

    public function handle(): int
    {
        $now = now();
        $currentPeriod = $now->format('Y-m');
        $nextPeriod = $now->copy()->addMonthNoOverflow()->format('Y-m');
        $allowedPeriods = [$currentPeriod, $nextPeriod];

        $existing = DB::table('tagihans')
            ->whereIn('periode', $allowedPeriods)
            ->select('pelanggan_id', 'periode')
            ->get()
            ->mapWithKeys(function ($r) {
                return [((int) $r->pelanggan_id) . '|' . (string) $r->periode => true];
            })
            ->all();

        $pelanggans = DB::table('pelanggans')
            ->whereIn('status_berlangganan', ['Aktif', 'Tunggakan'])
            ->select(
                'id',
                'nama',
                'ppn',
                'tanggal_daftar',
                'jatuh_tempo',
                'paket_layanan',
                'pending_paket_layanan',
                'pending_paket_effective_periode',
                'pending_paket_requested_by',
                'pending_paket_requested_at',
                'pending_paket_note',
                'balance',
                'is_generate_tagihan'
            )
            ->orderBy('id')
            ->get();

        $packageIds = [];
        foreach ($pelanggans as $p) {
            if (!empty($p->paket_layanan)) {
                $packageIds[(int) $p->paket_layanan] = true;
            }
            if (!empty($p->pending_paket_layanan)) {
                $packageIds[(int) $p->pending_paket_layanan] = true;
            }
        }
        $packagePrices = [];
        if (!empty($packageIds)) {
            $packages = DB::table('packages')->whereIn('id', array_keys($packageIds))->select('id', 'harga')->get();
            foreach ($packages as $pkg) {
                $packagePrices[(int) $pkg->id] = (int) ($pkg->harga ?? 0);
            }
        }

        $created = 0;
        $skipped = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($pelanggans as $p) {
            $pelangganId = (int) ($p->id ?? 0);
            if ($pelangganId < 1) {
                $skipped++;
                continue;
            }
            if (($p->is_generate_tagihan ?? 'Yes') !== 'Yes') {
                $skipped++;
                continue;
            }

            try {
                $tanggalDaftar = Carbon::parse($p->tanggal_daftar)->startOfDay();
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }
            $signupDay = (int) $tanggalDaftar->format('d');
            $extraDueDays = (int) ($p->jatuh_tempo ?? 0);

            $baseMonths = [
                $now->copy()->startOfMonth(),
                $now->copy()->addMonthNoOverflow()->startOfMonth(),
            ];

            $createdForCustomer = false;
            foreach ($baseMonths as $baseMonth) {
                $y = (int) $baseMonth->format('Y');
                $m = (int) $baseMonth->format('m');
                $lastDay = (int) $baseMonth->daysInMonth;
                $baseDay = $signupDay > $lastDay ? $lastDay : $signupDay;
                $baseDate = Carbon::create($y, $m, $baseDay, 0, 0, 0);
                $dueDate = $baseDate->copy()->addDays($extraDueDays);
                $periode = $dueDate->format('Y-m');
                if (!in_array($periode, $allowedPeriods, true)) {
                    continue;
                }
                $createAt = $dueDate->copy()->subDays(10)->startOfDay();
                if ($now->lessThan($createAt)) {
                    continue;
                }
                $key = $pelangganId . '|' . $periode;
                if (isset($existing[$key])) {
                    continue;
                }

                $packageIdUsed = !empty($p->paket_layanan) ? (int) $p->paket_layanan : 0;
                $pendingPackageId = !empty($p->pending_paket_layanan) ? (int) $p->pending_paket_layanan : 0;
                $pendingEffective = !empty($p->pending_paket_effective_periode) ? (string) $p->pending_paket_effective_periode : '';
                $isUsingPending = false;
                if ($pendingPackageId > 0 && $pendingEffective !== '' && $pendingEffective === $periode) {
                    $packageIdUsed = $pendingPackageId;
                    $isUsingPending = true;
                }
                $harga = (int) ($packagePrices[$packageIdUsed] ?? 0);
                if ($harga <= 0) {
                    $skipped++;
                    continue;
                }

                $ppn = (string) ($p->ppn ?? 'No');
                $nominalPpn = $ppn === 'Yes' ? (int) round($harga * 0.11) : 0;
                $totalBayar = $harga + $nominalPpn;

                if ($dryRun) {
                    $this->line("DRY-RUN create tagihan pelanggan_id={$pelangganId} periode={$periode} total={$totalBayar}");
                    $existing[$key] = true;
                    $created++;
                    $createdForCustomer = true;
                    continue;
                }

                DB::transaction(function () use (
                    $pelangganId,
                    $periode,
                    $harga,
                    $ppn,
                    $nominalPpn,
                    $totalBayar,
                    $isUsingPending,
                    $pendingPackageId
                ) {
                    $exists = DB::table('tagihans')->where('pelanggan_id', $pelangganId)->where('periode', $periode)->exists();
                    if ($exists) {
                        return;
                    }
                    $noTagihan = 'INV-SSL-' . Str::upper(Str::random(10));
                    DB::table('tagihans')->insert([
                        'no_tagihan' => $noTagihan,
                        'pelanggan_id' => $pelangganId,
                        'periode' => $periode,
                        'status_bayar' => 'Belum Bayar',
                        'nominal_bayar' => $harga,
                        'potongan_bayar' => 0,
                        'ppn' => $ppn,
                        'nominal_ppn' => $nominalPpn,
                        'total_bayar' => $totalBayar,
                        'tanggal_create_tagihan' => now(),
                        'is_send' => 'No',
                        'created_by' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($isUsingPending) {
                        DB::table('pelanggans')->where('id', $pelangganId)->update([
                            'paket_layanan' => $pendingPackageId,
                            'pending_paket_layanan' => null,
                            'pending_paket_effective_periode' => null,
                            'pending_paket_requested_by' => null,
                            'pending_paket_requested_at' => null,
                            'pending_paket_note' => null,
                            'updated_at' => now(),
                        ]);
                    }
                });

                $existing[$key] = true;
                $created++;
                $createdForCustomer = true;

                autoPayTagihanWithSaldo($pelangganId);
            }

            if (!$createdForCustomer) {
                $skipped++;
            }
        }

        $msg = "tagihan:create done. customers={$pelanggans->count()} created={$created} skipped={$skipped} allowed=" . implode(',', $allowedPeriods);
        $this->info($msg);
        Log::info($msg);
        return self::SUCCESS;
    }
}

