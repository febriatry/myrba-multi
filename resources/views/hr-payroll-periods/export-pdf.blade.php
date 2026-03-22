<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Payroll' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 5px 5px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="title">{{ $title ?? 'Payroll' }}</div>
    <div class="meta">
        Periode: {{ $row->period_start ?? '-' }} - {{ $row->period_end ?? '-' }} |
        Status: {{ $row->status ?? '-' }} |
        Generated: {{ $row->generated_at ?? '-' }}
    </div>

    <table style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Hadir</th>
                <th>Gaji Pokok</th>
                <th>Lembur</th>
                <th>Operasional</th>
                <th>Pot Wajib</th>
                <th>Sanksi</th>
                <th>Potongan</th>
                <th>Kasbon</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $it)
                @php
                    $meta = [];
                    if (!empty($it->meta)) {
                        $meta = json_decode($it->meta, true) ?: [];
                    }
                    $opAuto = (int) ($meta['operational_auto'] ?? 0);
                    $opManual = (int) ($meta['operational_manual'] ?? 0);
                @endphp
                <tr>
                    <td>
                        {{ $it->user_name ?? '-' }}<br>
                        <span style="color:#6b7280">{{ $it->jabatan_name ?? '-' }}</span>
                    </td>
                    <td class="text-right">{{ $it->present_days }}</td>
                    <td class="text-right">{{ $it->base_amount }}</td>
                    <td class="text-right">{{ $it->overtime_amount }}</td>
                    <td class="text-right">{{ $it->operational_amount }}<br><span style="color:#6b7280">auto {{ $opAuto }} | manual {{ $opManual }}</span></td>
                    <td class="text-right">{{ $it->mandatory_deduction_amount }}</td>
                    <td class="text-right">{{ $it->sanction_deduction_amount }}</td>
                    <td class="text-right">{{ $it->other_deduction_amount ?? 0 }}</td>
                    <td class="text-right">{{ $it->kasbon_deduction_amount ?? 0 }}</td>
                    <td class="text-right"><b>{{ $it->total_amount }}</b></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th class="text-right">{{ $summary['present_days'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['base_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['overtime_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['operational_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['mandatory_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['sanction_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['other_deduction_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['kasbon_deduction_total'] ?? 0 }}</th>
                <th class="text-right">{{ $summary['grand_total'] ?? 0 }}</th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
