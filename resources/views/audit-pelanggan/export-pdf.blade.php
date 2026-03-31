<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Audit Pelanggan' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .meta {
            font-size: 10px;
            color: #374151;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            margin: 12px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 6px 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="title">{{ $title ?? 'Audit Pelanggan' }}</div>
    @if (!empty($meta))
        <div class="meta">{!! $meta !!}</div>
    @endif

    <div class="section-title">{{ __('Ringkasan') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Pelanggan Total') }}</th>
                <th>{{ __('Pelanggan Aktif (DB)') }}</th>
                <th>{{ __('PPP Active (Mikrotik)') }}</th>
                <th>{{ __('PPP Secret Total (Mikrotik)') }}</th>
                <th>{{ __('PPP Non Active (Mikrotik)') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ (int) ($summary['pelanggan_total'] ?? 0) }}</td>
                <td class="text-center">{{ (int) ($summary['pelanggan_aktif'] ?? 0) }}</td>
                <td class="text-center">{{ (int) ($summary['ppp_active_total'] ?? 0) }}</td>
                <td class="text-center">{{ (int) ($summary['ppp_secret_total'] ?? 0) }}</td>
                <td class="text-center">{{ (int) ($summary['ppp_non_active_total'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">{{ __('1) PPP Secret tanpa Pelanggan (Orphan)') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Router') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Disabled') }}</th>
                <th>{{ __('Profile') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($anomali['orphan_secrets'] ?? []) as $r)
                <tr>
                    <td>{{ $r['router_name'] ?? '-' }}</td>
                    <td>{{ $r['name'] ?? '-' }}</td>
                    <td>{{ $r['disabled'] ?? '-' }}</td>
                    <td>{{ $r['profile'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">{{ __('Tidak ada.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('2) Pelanggan PPOE tanpa Secret (Missing Secret)') }}</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('Nama') }}</th>
                <th>{{ __('No Layanan') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Router') }}</th>
                <th>{{ __('User PPPoE') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($anomali['missing_secrets'] ?? []) as $r)
                <tr>
                    <td>{{ $r['pelanggan_id'] ?? '-' }}</td>
                    <td>{{ $r['nama'] ?? '-' }}</td>
                    <td>{{ isset($r['no_layanan']) ? formatNoLayananTenant($r['no_layanan'], (int) (auth()->user()->tenant_id ?? 0)) : '-' }}</td>
                    <td>{{ $r['status'] ?? '-' }}</td>
                    <td>{{ $r['router_id'] ?? '-' }}</td>
                    <td>{{ $r['user_pppoe'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">{{ __('Tidak ada.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('3) PPP Active Mismatch') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Router') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Pelanggan') }}</th>
                <th>{{ __('Status') }}</th>
                <th>IP</th>
                <th>Uptime</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($anomali['active_mismatch'] ?? []) as $r)
                <tr>
                    <td>{{ $r['type'] ?? '-' }}</td>
                    <td>{{ $r['router_name'] ?? '-' }}</td>
                    <td>{{ $r['user_pppoe'] ?? '-' }}</td>
                    <td>{{ ($r['pelanggan_id'] ?? '-') . ' ' . ($r['nama'] ?? '') }}</td>
                    <td>{{ $r['status'] ?? '-' }}</td>
                    <td>{{ $r['address'] ?? '-' }}</td>
                    <td>{{ $r['uptime'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">{{ __('Tidak ada.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('4) Duplikasi user_pppoe di Database') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Router ID') }}</th>
                <th>{{ __('User PPPoE') }}</th>
                <th>{{ __('Detail') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($anomali['duplicates'] ?? []) as $d)
                <tr>
                    <td class="text-center">{{ $d['router_id'] ?? '-' }}</td>
                    <td>{{ $d['user_pppoe'] ?? '-' }}</td>
                    <td>
                        @foreach (($d['rows'] ?? []) as $r)
                            {{ ($r->id ?? '-') . ' - ' . (isset($r->no_layanan) ? formatNoLayananTenant($r->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) : '-') . ' - ' . ($r->nama ?? '-') . ' - ' . ($r->status_berlangganan ?? '-') }}<br>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">{{ __('Tidak ada.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('5) Router Error') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Router') }}</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($routerErrors ?? []) as $e)
                <tr>
                    <td>{{ $e['router_name'] ?? '-' }}</td>
                    <td>{{ $e['error'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center">{{ __('Tidak ada.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
