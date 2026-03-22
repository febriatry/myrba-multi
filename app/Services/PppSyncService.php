<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PppSyncService
{
    public static function sync(?int $onlyRouterId = null): array
    {
        $routerSelect = ['id', 'identitas_router', 'host', 'port', 'username', 'password'];
        $hasIsActive = Schema::hasTable('settingmikrotiks') && Schema::hasColumn('settingmikrotiks', 'is_active');
        if ($hasIsActive) {
            $routerSelect[] = 'is_active';
        }

        $routers = DB::table('settingmikrotiks')
            ->select($routerSelect)
            ->when(!empty($onlyRouterId), function ($q) use ($onlyRouterId) {
                $q->where('id', (int) $onlyRouterId);
            })
            ->orderBy('identitas_router')
            ->get();

        $report = [
            'total_routers' => (int) $routers->count(),
            'processed' => 0,
            'errors' => [],
            'routers' => [],
        ];

        foreach ($routers as $r) {
            if ($hasIsActive && !empty($r->is_active) && (string) $r->is_active !== 'Yes') {
                continue;
            }

            $routerId = (int) $r->id;
            $routerLabel = (string) ($r->identitas_router ?? ('Router ' . $routerId));
            $row = [
                'router_id' => $routerId,
                'router' => $routerLabel,
                'active' => 0,
                'secret' => 0,
                'profile' => 0,
                'status' => 'ok',
            ];

            try {
                $client = new \RouterOS\Client([
                    'host' => (string) $r->host,
                    'user' => (string) $r->username,
                    'pass' => (string) $r->password,
                    'port' => (int) ($r->port ?? 8728),
                ]);

                $actives = $client->query(new \RouterOS\Query('/ppp/active/print'))->read();
                $secrets = $client->query(new \RouterOS\Query('/ppp/secret/print'))->read();
                $profiles = $client->query(new \RouterOS\Query('/ppp/profile/print'))->read();

                $now = now();
                DB::transaction(function () use ($routerId, $actives, $secrets, $profiles, $now, &$row) {
                    DB::table('active_ppps')->where('router_id', $routerId)->delete();
                    DB::table('secret_ppps')->where('router_id', $routerId)->delete();
                    DB::table('profile_pppoes')->where('router_id', $routerId)->delete();

                    $activeRows = [];
                    foreach ($actives as $a) {
                        $name = trim((string) ($a['name'] ?? ''));
                        if ($name === '') {
                            continue;
                        }
                        $activeRows[] = [
                            'router_id' => $routerId,
                            'name' => mb_substr($name, 0, 100),
                            'service' => mb_substr(trim((string) ($a['service'] ?? '')), 0, 50) ?: null,
                            'caller_id' => mb_substr(trim((string) ($a['caller-id'] ?? '')), 0, 100) ?: null,
                            'ip_address' => mb_substr(trim((string) ($a['address'] ?? '')), 0, 50) ?: null,
                            'uptime' => mb_substr(trim((string) ($a['uptime'] ?? '')), 0, 50) ?: null,
                            'komentar' => mb_substr(trim((string) ($a['comment'] ?? '')), 0, 255) ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($activeRows)) {
                        DB::table('active_ppps')->insert($activeRows);
                    }
                    $row['active'] = count($activeRows);

                    $secretRows = [];
                    foreach ($secrets as $s) {
                        $username = trim((string) ($s['name'] ?? ''));
                        if ($username === '') {
                            continue;
                        }
                        $disabledRaw = $s['disabled'] ?? null;
                        $disabled = ($disabledRaw === 'true' || $disabledRaw === true);
                        $status = $disabled ? 'disabled' : 'enabled';
                        $secretRows[] = [
                            'router_id' => $routerId,
                            'username' => mb_substr($username, 0, 100),
                            'password' => mb_substr(trim((string) ($s['password'] ?? '')), 0, 255) ?: null,
                            'service' => mb_substr(trim((string) ($s['service'] ?? '')), 0, 50) ?: null,
                            'profile' => mb_substr(trim((string) ($s['profile'] ?? '')), 0, 100) ?: null,
                            'last_logout' => mb_substr(trim((string) ($s['last-logged-out'] ?? '')), 0, 50) ?: null,
                            'komentar' => mb_substr(trim((string) ($s['comment'] ?? '')), 0, 255) ?: null,
                            'status' => $status,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($secretRows)) {
                        DB::table('secret_ppps')->insert($secretRows);
                    }
                    $row['secret'] = count($secretRows);

                    $profileRows = [];
                    foreach ($profiles as $p) {
                        $name = trim((string) ($p['name'] ?? ''));
                        if ($name === '') {
                            continue;
                        }
                        $profileRows[] = [
                            'router_id' => $routerId,
                            'name' => mb_substr($name, 0, 100),
                            'local' => mb_substr(trim((string) ($p['local-address'] ?? '')), 0, 50) ?: null,
                            'remote' => mb_substr(trim((string) ($p['remote-address'] ?? '')), 0, 50) ?: null,
                            'limit' => mb_substr(trim((string) ($p['rate-limit'] ?? '')), 0, 50) ?: null,
                            'parent' => mb_substr(trim((string) ($p['parent-queue'] ?? '')), 0, 100) ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($profileRows)) {
                        DB::table('profile_pppoes')->insert($profileRows);
                    }
                    $row['profile'] = count($profileRows);
                });
            } catch (\Throwable $e) {
                $row['status'] = 'error';
                $report['errors'][] = [
                    'router_id' => $routerId,
                    'router' => $routerLabel,
                    'error' => $e->getMessage(),
                ];
            }

            $report['processed']++;
            $report['routers'][] = $row;
        }

        return $report;
    }
}
