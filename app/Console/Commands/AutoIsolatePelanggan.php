<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class AutoIsolatePelanggan extends Command
{
    protected $signature = 'pelanggan:auto-isolate {--dry-run : Tampilkan data tanpa melakukan perubahan}';

    protected $description = 'Menonaktifkan pelanggan otomatis jika melewati jatuh tempo dan masih punya tagihan belum bayar';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $settings = $this->getSettings();
        if (($settings['is_enabled'] ?? 'No') !== 'Yes') {
            $this->info('Auto isolir nonaktif (settings).');
            return Command::SUCCESS;
        }
        $rows = $this->getTargetPelanggan();

        $this->info('Target pelanggan: ' . $rows->count());

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $processed++;

            if ($dryRun) {
                $this->line("DRY-RUN pelanggan_id={$row->pelanggan_id} mode={$row->mode_user}");
                $this->logAction('isolate', $row, 'scheduler_dry_run', 'skipped', null);
                continue;
            }

            try {
                $client = setRouteTagihanByPelanggan($row->router);
                if (!$client) {
                    $skipped++;
                    $this->logAction('isolate', $row, 'scheduler', 'skipped', 'router tidak ditemukan');
                    continue;
                }

                if ($row->mode_user === 'PPOE') {
                    $this->isolatePppoe($client, $row->user_pppoe);
                } else {
                    $this->isolateStatic($client, $row->user_static);
                }

                DB::table('pelanggans')
                    ->where('id', $row->pelanggan_id)
                    ->update([
                        'status_berlangganan' => 'Non Aktif',
                        'updated_at' => now(),
                    ]);
                $this->logAction('isolate', $row, 'scheduler', 'ok', null);
            } catch (\Throwable $e) {
                $failed++;
                Log::error('auto isolate pelanggan gagal', [
                    'pelanggan_id' => $row->pelanggan_id,
                    'error' => $e->getMessage(),
                ]);
                $this->logAction('isolate', $row, 'scheduler', 'failed', $e->getMessage());
            }
        }

        $this->info("Selesai. diproses={$processed}, gagal={$failed}, skip={$skipped}");

        return Command::SUCCESS;
    }

    private function getTargetPelanggan()
    {
        $settings = $this->getSettings();
        $minUnpaid = (int) ($settings['min_unpaid_invoices'] ?? 1);
        $overdueOnly = (string) ($settings['overdue_only'] ?? 'Yes');
        $includeWaitingReview = (string) ($settings['include_waiting_review'] ?? 'Yes');
        $respectFlag = (string) ($settings['respect_pelanggan_auto_isolir'] ?? 'Yes');
        $scopeType = (string) ($settings['scope_type'] ?? 'All');
        $scopeAreaIds = (array) ($settings['scope_area_ids'] ?? []);

        $unpaidStatuses = ['Belum Bayar'];
        if ($includeWaitingReview === 'Yes') {
            $unpaidStatuses[] = 'Waiting Review';
        }

        return DB::table('pelanggans as p')
            ->select(
                'p.id as pelanggan_id',
                'p.router',
                'p.mode_user',
                'p.user_pppoe',
                'p.user_static'
            )
            ->when($respectFlag === 'Yes', function ($q) {
                $q->where('p.auto_isolir', 'Yes');
            })
            ->where('p.status_berlangganan', '!=', 'Non Aktif')
            ->whereNotNull('p.router')
            ->when($scopeType === 'AreaCoverage' && !empty($scopeAreaIds), function ($q) use ($scopeAreaIds) {
                $q->whereIn('p.coverage_area', array_values(array_map('intval', $scopeAreaIds)));
            })
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->where('p.mode_user', 'PPOE')->whereNotNull('p.user_pppoe');
                })->orWhere(function ($qq) {
                    $qq->where('p.mode_user', '!=', 'PPOE')->whereNotNull('p.user_static');
                });
            })
            ->whereExists(function ($q) use ($unpaidStatuses) {
                $in = "'" . implode("','", array_map(function ($s) {
                    return str_replace("'", "''", (string) $s);
                }, $unpaidStatuses)) . "'";
                $q->select(DB::raw(1))
                    ->from('tagihans as t')
                    ->whereColumn('t.pelanggan_id', 'p.id')
                    ->whereRaw("t.status_bayar IN ($in)");
            })
            ->when($overdueOnly === 'Yes', function ($q) use ($unpaidStatuses) {
                $in = "'" . implode("','", array_map(function ($s) {
                    return str_replace("'", "''", (string) $s);
                }, $unpaidStatuses)) . "'";
                $q->whereExists(function ($qq) use ($in) {
                    $qq->select(DB::raw(1))
                        ->from('tagihans as t2')
                        ->whereColumn('t2.pelanggan_id', 'p.id')
                        ->whereRaw("t2.status_bayar IN ($in)")
                        ->whereRaw('DATE_ADD(t2.tanggal_create_tagihan, INTERVAL COALESCE(p.jatuh_tempo, 0) DAY) < NOW()');
                });
            })
            ->whereRaw("(SELECT COUNT(*) FROM tagihans t3 WHERE t3.pelanggan_id = p.id AND t3.status_bayar IN ('" . implode("','", $unpaidStatuses) . "')) >= " . $minUnpaid)
            ->get();
    }

    private function isolatePppoe($client, string $userPppoe): void
    {
        $data = $client->query((new Query('/ppp/secret/print'))->where('name', $userPppoe))->read();
        if (empty($data) || !isset($data[0]['.id'])) {
            return;
        }

        $idSecret = $data[0]['.id'];
        $existingComment = $data[0]['comment'] ?? null;
        $comment = myrbaMergeMikrotikComment($existingComment, 'Isolir otomatis (cron)');
        $client->query((new Query('/ppp/secret/set'))
            ->equal('.id', $idSecret)
            ->equal('comment', $comment))->read();
        $client->query((new Query('/ppp/secret/disable'))->equal('.id', $idSecret))->read();

        $active = $client->query((new Query('/ppp/active/print'))->where('name', $userPppoe))->read();
        if (!empty($active) && isset($active[0]['.id'])) {
            $client->query((new Query('/ppp/active/remove'))->equal('.id', $active[0]['.id']))->read();
        }
    }

    private function isolateStatic($client, string $userStatic): void
    {
        $data = $client->query((new Query('/queue/simple/print'))->where('name', $userStatic))->read();
        if (empty($data) || !isset($data[0]['target'])) {
            return;
        }

        $ip = explode('/', $data[0]['target'])[0] ?? null;
        if (!$ip) {
            return;
        }

        $exists = $client->query((new Query('/ip/firewall/address-list/print'))
            ->where('list', 'expired')
            ->where('address', $ip))->read();

        if (empty($exists)) {
            $client->query((new Query('/ip/firewall/address-list/add'))
                ->equal('list', 'expired')
                ->equal('address', $ip))->read();
        }
    }

    private function getSettings(): array
    {
        $row = DB::table('mikrotik_automation_settings')->first();
        $defaults = [
            'is_enabled' => 'No',
            'respect_pelanggan_auto_isolir' => 'Yes',
            'min_unpaid_invoices' => 1,
            'overdue_only' => 'Yes',
            'include_waiting_review' => 'Yes',
            'scope_type' => 'All',
            'scope_area_ids' => [],
        ];
        if (!$row) {
            return $defaults;
        }
        $scopeIds = [];
        if (!empty($row->scope_area_ids)) {
            $decoded = json_decode($row->scope_area_ids, true);
            if (is_array($decoded)) {
                $scopeIds = array_values(array_filter(array_map('intval', $decoded)));
            }
        }
        return [
            'is_enabled' => (string) ($row->is_enabled ?? $defaults['is_enabled']),
            'respect_pelanggan_auto_isolir' => (string) ($row->respect_pelanggan_auto_isolir ?? $defaults['respect_pelanggan_auto_isolir']),
            'min_unpaid_invoices' => (int) ($row->min_unpaid_invoices ?? $defaults['min_unpaid_invoices']),
            'overdue_only' => (string) ($row->overdue_only ?? $defaults['overdue_only']),
            'include_waiting_review' => (string) ($row->include_waiting_review ?? $defaults['include_waiting_review']),
            'scope_type' => (string) ($row->scope_type ?? $defaults['scope_type']),
            'scope_area_ids' => $scopeIds,
        ];
    }

    private function logAction(string $action, $row, string $reason, string $status, ?string $errorMessage = null): void
    {
        try {
            DB::table('mikrotik_action_logs')->insert([
                'action' => $action,
                'pelanggan_id' => !empty($row->pelanggan_id) ? (int) $row->pelanggan_id : null,
                'router_id' => !empty($row->router) ? (int) $row->router : null,
                'mode_user' => !empty($row->mode_user) ? (string) $row->mode_user : null,
                'identity' => !empty($row->mode_user) && (string) $row->mode_user === 'PPOE'
                    ? (string) ($row->user_pppoe ?? '')
                    : (string) ($row->user_static ?? ''),
                'reason' => $reason,
                'status' => $status,
                'error_message' => $errorMessage,
                'performed_by' => null,
                'performed_via' => 'scheduler',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
