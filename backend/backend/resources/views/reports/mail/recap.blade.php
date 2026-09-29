@component('mail::message')
# Rekap {{ $report->type->label() }} Booking

Halo {{ $name }},

Berikut rekap booking di **{{ $lab->name }}** untuk periode
**{{ $report->period_start->translatedFormat('d M Y') }}** – **{{ $report->period_end->translatedFormat('d M Y') }}**.

- Total booking: **{{ $report->total_bookings }}**
- Total pendapatan (lunas): **Rp{{ number_format((float) $report->total_revenue, 0, ',', '.') }}**

File rekap lengkap (Excel & PDF) terlampir di email ini.

@component('mail::button', ['url' => config('app.frontend_url') . '/dashboard/lab/' . $lab->slug . '/reports'])
Lihat di Dashboard
@endcomponent

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent