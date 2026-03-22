<?php

namespace App\Console\Commands;

use App\Services\PppSyncService;
use Illuminate\Console\Command;

class SyncPppMikrotik extends Command
{
    protected $signature = 'mikrotik:sync-ppp {--router_id=}';
    protected $description = 'Sync PPP data (active/secret/profile) from Mikrotik routers into local tables';

    public function handle(): int
    {
        $routerIdOpt = $this->option('router_id');
        $routerId = !empty($routerIdOpt) ? (int) $routerIdOpt : null;

        $report = PppSyncService::sync($routerId);
        $this->info('Routers processed: ' . (string) ($report['processed'] ?? 0));
        foreach (($report['routers'] ?? []) as $r) {
            $this->line(($r['router'] ?? '-') . ' | active=' . ($r['active'] ?? 0) . ' secret=' . ($r['secret'] ?? 0) . ' profile=' . ($r['profile'] ?? 0) . ' | ' . ($r['status'] ?? ''));
        }
        foreach (($report['errors'] ?? []) as $e) {
            $this->error(($e['router'] ?? '-') . ': ' . ($e['error'] ?? 'error'));
        }

        return empty($report['errors']) ? self::SUCCESS : self::FAILURE;
    }
}

