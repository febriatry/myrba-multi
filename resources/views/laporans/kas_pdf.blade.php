<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Kas: {{ $start }} s/d {{ $end }}</title>
    <style>
        body { font-family: "Courier New", "DejaVu Sans Mono", monospace; font-size: 11px; color: #000; }
        h1, h2, h3 { margin: 0 0 6px 0; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { padding: 4px 6px; border-bottom: 1px dashed #000; }
        th { text-transform: uppercase; font-weight: bold; border-top: 1px dashed #000; }
        .section { margin-top: 12px; }
        .totals { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Buku Kas</h1>
    <div>Periode: {{ $start }} s/d {{ $end }}</div>

    <div class="section">
        <h2>Buku Kas</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Pemasukan</th>
                    <th>Pengeluaran</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($ledger as $row)
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['kategori'] }}</td>
                    <td>{{ $row['keterangan'] }}</td>
                    <td style="text-align:right">{{ rupiah($row['pemasukan']) }}</td>
                    <td style="text-align:right">{{ rupiah($row['pengeluaran']) }}</td>
                    <td style="text-align:right">{{ rupiah($row['saldo']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center">Tidak ada data</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="totals"><strong>Total Pemasukan:</strong> {{ rupiah($totalIncome) }}</div>
        <div class="totals"><strong>Total Pengeluaran:</strong> {{ rupiah($totalExpenses) }}</div>
        <div class="totals"><strong>Saldo Akhir:</strong> {{ rupiah($totalIncome - $totalExpenses) }}</div>
    </div>

    <div class="section">
        <h3>Rekap</h3>
        <table>
            <tr>
                <th>Total Pemasukan</th>
                <td style="text-align:right">{{ rupiah($totalIncome) }}</td>
            </tr>
            <tr>
                <th>Total Pengeluaran</th>
                <td style="text-align:right">{{ rupiah($totalExpenses) }}</td>
            </tr>
            <tr>
                <th>Saldo (Pemasukan - Pengeluaran)</th>
                <td style="text-align:right">{{ rupiah($totalIncome - $totalExpenses) }}</td>
            </tr>
        </table>
    </div>
</body>
