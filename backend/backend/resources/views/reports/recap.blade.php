<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    p.meta { color: #6b7280; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
    th { background: #f9fafb; }
    .summary td { border: none; padding: 2px 8px 2px 0; }
</style>
</head>
<body>
    <h1>Rekap {{ $type->label() }} Booking — {{ $lab->name }}</h1>
    <p class="meta">Periode: {{ $start->translatedFormat('d M Y') }} – {{ $end->translatedFormat('d M Y') }}</p>

    <table class="summary">
        <tr><td><strong>Total Booking</strong></td><td>{{ $bookings->count() }}</td></tr>
        <tr><td><strong>Total Pendapatan (Lunas)</strong></td><td>Rp{{ number_format($totalRevenue, 0, ',', '.') }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Kode</th><th>Customer</th><th>NIM</th><th>Tipe</th>
                <th>Status</th><th>Pembayaran</th><th>Total</th><th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $b)
                <tr>
                    <td>{{ $b->booking_code }}</td>
                    <td>{{ $b->user?->name ?? '-' }}</td>
                    <td>{{ $b->user?->nim ?? '-' }}</td>
                    <td>{{ $b->booking_type->label() }}</td>
                    <td>{{ $b->status->label() }}</td>
                    <td>{{ $b->payment_status->label() }}</td>
                    <td>Rp{{ number_format((float) $b->total_price, 0, ',', '.') }}</td>
                    <td>{{ $b->created_at->format('d-m-Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Tidak ada booking pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>