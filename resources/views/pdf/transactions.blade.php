<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #615cf9; padding-bottom: 12px; }
        .header h1 { font-size: 16px; margin: 0 0 4px; color: #615cf9; }
        .header p { font-size: 10px; color: #6b7280; margin: 2px 0; }
        .filter { font-size: 9px; color: #9ca3af; margin-bottom: 12px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; padding: 8px 6px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        td { padding: 7px 6px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 12px; font-size: 8px; font-weight: bold; }
        .badge-booked { background: #eef0ff; color: #4f46e5; }
        .badge-picked_up { background: #eaf2ff; color: #2563eb; }
        .badge-returned { background: #eafbf4; color: #059669; }
        .badge-completed { background: #eafbf4; color: #059669; }
        .badge-overdue { background: #feecec; color: #dc2626; }
        .badge-cancelled { background: #f3f4f6; color: #6b7280; }
        .footer { margin-top: 20px; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $store['name'] }}</h1>
        <p>{{ $store['address'] }}</p>
        <p>{{ $store['whatsapp_number'] }}</p>
        <h2 style="font-size:14px; margin:10px 0 0;">{{ $title }}</h2>
    </div>

    @if($filters)
        <div class="filter">Filter: {{ $filters }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Tgl Dibuat</th>
                <th>Jadwal Ambil</th>
                <th>Jadwal Kembali</th>
                <th>Status</th>
                <th>Pembayaran</th>
                <th class="text-right">Total</th>
                <th class="text-right">Dibayar</th>
                <th class="text-right">Sisa</th>
                <th class="text-right">Denda</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rentals as $rental)
                <tr>
                    <td>{{ $rental->invoice_number }}</td>
                    <td>{{ $rental->customer?->name ?? '-' }}</td>
                    <td>{{ $rental->created_at->format('d/m/Y') }}</td>
                    <td>{{ $rental->pickup_at ? $rental->pickup_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $rental->return_due_at ? $rental->return_due_at->format('d/m/Y H:i') : '-' }}</td>
                    <td><span class="badge badge-{{ $rental->status }}">{{ $rental->status }}</span></td>
                    <td><span class="badge badge-{{ $rental->payment_status }}">{{ $rental->payment_status }}</span></td>
                    <td class="text-right">Rp{{ number_format($rental->total_amount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($rental->paid_amount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($rental->remaining_amount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($rental->penalty_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d M Y H:i') }} | {{ $store['name'] }}
    </div>
</body>
</html>
