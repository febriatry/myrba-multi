<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Setor' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; }
        .meta { margin-top: 6px; font-size: 10px; color: #333; }
        .meta-row { margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px 6px; vertical-align: top; }
        th { background: #f2f2f2; font-weight: bold; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .section-title { margin-top: 10px; font-weight: bold; }
        .signature { margin-top: 20px; width: 100%; }
        .signature td { border: none; padding: 10px 6px; }
        .line { margin-top: 40px; border-top: 1px solid #222; width: 240px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title ?? 'Laporan Setor' }}</div>
        <div class="meta">
            <div class="meta-row">Kode: {{ $setor->code ?? '-' }}</div>
            <div class="meta-row">Tanggal Setor: {{ $setor->deposited_at ?? '-' }}</div>
            <div class="meta-row">Penyetor: {{ $setor->depositor_name ?? '-' }}</div>
            <div class="meta-row">Metode: {{ $setor->method ?? '-' }}</div>
            @if (!empty($setor->bank_name))
                <div class="meta-row">Bank: {{ $setor->bank_name }} {{ $setor->bank_number }} ({{ $setor->bank_owner }})</div>
            @endif
        </div>
    </div>

    @foreach ($grouped as $areaName => $g)
        <div class="section-title">Area: {{ $areaName }}</div>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width: 36px;">No</th>
                    <th style="width: 120px;">No Layanan</th>
                    <th>Nama Pelanggan</th>
                    <th style="width: 110px;">Bulan</th>
                    <th class="num" style="width: 120px;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($g['items'] as $i => $it)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ formatNoLayananTenant($it->no_layanan ?? '-', $tenantId ?? 0) }}</td>
                        <td>{{ $it->pelanggan_nama ?? '-' }}</td>
                        <td>{{ $it->periode ?? '-' }}</td>
                        <td class="num">{{ rupiah((int) ($it->nominal ?? 0)) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4" class="num"><strong>Subtotal</strong></td>
                    <td class="num"><strong>{{ rupiah((int) ($g['subtotal'] ?? 0)) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <div class="section-title">Total</div>
    <table>
        <tbody>
            <tr>
                <td>Total Items</td>
                <td class="num">{{ (int) ($totalItems ?? 0) }}</td>
                <td>Total Nominal</td>
                <td class="num">{{ rupiah((int) ($totalNominal ?? 0)) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td style="width: 50%; text-align: left;">
                <div>Penyetor</div>
                <div class="line"></div>
                <div>{{ $setor->depositor_name ?? '-' }}</div>
            </td>
            <td style="width: 50%; text-align: left;">
                <div>Penerima (Keuangan)</div>
                <div class="line"></div>
                <div>&nbsp;</div>
            </td>
        </tr>
    </table>
</body>
</html>
