<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GenieAcsNbiService
{
    private static function client(): PendingRequest
    {
        $baseUrl = rtrim((string) config('genieacs.nbi_base_url'), '/');
        $timeout = (int) config('genieacs.timeout', 15);
        $user = (string) config('genieacs.username', '');
        $pass = (string) config('genieacs.password', '');
        $verifyTls = (bool) config('genieacs.verify_tls', true);

        $req = Http::timeout($timeout)->acceptJson()->withOptions(['verify' => $verifyTls]);
        if ($baseUrl !== '') {
            $req = $req->baseUrl($baseUrl);
        }
        if ($user !== '' || $pass !== '') {
            $req = $req->withBasicAuth($user, $pass);
        }

        return $req;
    }

    public static function searchDevices(array $query, array $projection = []): array
    {
        $params = ['query' => json_encode($query)];
        if (! empty($projection)) {
            $params['projection'] = implode(',', $projection);
        }

        $res = self::client()->get('/devices/', $params);
        $res->throw();

        return (array) $res->json();
    }

    public static function getDevice(string $deviceId, array $projection = []): ?array
    {
        $list = self::searchDevices(['_id' => $deviceId], $projection);
        if (empty($list) || ! isset($list[0])) {
            return null;
        }

        return (array) $list[0];
    }

    public static function findDeviceByIdContains(string $needle, int $limit = 5): array
    {
        $q = [
            '_id' => [
                '$regex' => preg_quote($needle, '/'),
                '$options' => 'i',
            ],
        ];

        $res = self::client()->get('/devices/', [
            'query' => json_encode($q),
            'limit' => $limit,
        ]);
        $res->throw();

        return (array) $res->json();
    }

    public static function findDevicesBySerial(string $serial, int $limit = 10): array
    {
        $s = trim($serial);
        if ($s === '') {
            return [];
        }

        $rx = preg_quote($s, '/');
        $q = [
            '$or' => [
                ['DeviceID.SerialNumber' => ['$regex' => $rx, '$options' => 'i']],
                ['InternetGatewayDevice.DeviceInfo.SerialNumber' => ['$regex' => $rx, '$options' => 'i']],
                ['Device.DeviceInfo.SerialNumber' => ['$regex' => $rx, '$options' => 'i']],
                ['_id' => ['$regex' => $rx, '$options' => 'i']],
            ],
        ];

        $res = self::client()->get('/devices/', [
            'query' => json_encode($q),
            'limit' => $limit,
            'projection' => implode(',', [
                '_id',
                '_lastInform',
                '_tags',
                'DeviceID.Manufacturer',
                'DeviceID.ProductClass',
                'DeviceID.SerialNumber',
                'InternetGatewayDevice.DeviceInfo.Manufacturer',
                'InternetGatewayDevice.DeviceInfo.ModelName',
                'InternetGatewayDevice.DeviceInfo.SerialNumber',
                'Device.DeviceInfo.Manufacturer',
                'Device.DeviceInfo.ModelName',
                'Device.DeviceInfo.SerialNumber',
            ]),
        ]);
        $res->throw();

        return (array) $res->json();
    }

    public static function tagDevice(string $deviceId, string $tag): void
    {
        $tag = trim($tag);
        if ($tag === '') {
            return;
        }

        $res = self::client()->post('/devices/'.rawurlencode($deviceId).'/tags/'.rawurlencode($tag));
        $res->throw();
    }

    public static function enqueueTask(string $deviceId, array $task, bool $connectionRequest = true): array
    {
        $url = '/devices/'.rawurlencode($deviceId).'/tasks';
        if ($connectionRequest) {
            $url .= '?connection_request';
        }

        $res = self::client()->withHeaders(['Content-Type' => 'application/json'])->post($url, $task);
        $res->throw();

        return (array) $res->json();
    }

    public static function refreshAll(string $deviceId, bool $connectionRequest = true): array
    {
        return self::enqueueTask($deviceId, ['name' => 'refreshObject', 'objectName' => ''], $connectionRequest);
    }

    public static function reboot(string $deviceId, bool $connectionRequest = true): array
    {
        return self::enqueueTask($deviceId, ['name' => 'reboot'], $connectionRequest);
    }
}
