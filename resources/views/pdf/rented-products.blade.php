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
        .footer { margin-top: 20px; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .summary { background: #f3f4f6; padding: 8px 12px; border-radius: 6px; margin-top: 12px; font-size: 10px; font-weight: bold; }
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
                <th>Produk</th>
                <th>Kode</th>
                <th>Varian</th>
                <th>SKU</th>
                <th class="text-right">Qty Disewa</th>
                <th class="text-right">Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? $item->item_name_snapshot }}</td>
                    <td>{{ $item->product?->code ?? '-' }}</td>
                    <td>{{ $item->productVariant?->name ?? $item->variant_name_snapshot ?? '-' }}</td>
                    <td>{{ $item->productVariant?->sku ?? '-' }}</td>
                    <td class="text-right">{{ $item->total_quantity }}</td>
                    <td class="text-right">Rp{{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        Total produk: {{ $items->count() }} | Total qty: {{ $items->sum('total_quantity') }} | Total pendapatan: Rp{{ number_format($items->sum('total_revenue'), 0, ',', '.') }}
    </div>

    <div class="footer">
        Dicetak pada {{ now()->format('d M Y H:i') }} | {{ $store['name'] }}
    </div>
</body>
</html>
