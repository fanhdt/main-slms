<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Booking\Models\Booking;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class PaymentService extends BaseService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Generate Snap Token untuk booking.
     * order_id dibuat dengan timestamp presisi detik + random suffix
     * supaya tidak collision walau dipanggil 2x berturut-turut (double click, retry, dll).
     */
    public function createSnapToken(Booking $booking): string
    {
        if ($booking->payment_status === PaymentStatus::Paid) {
            throw ApiException::conflict('Booking ini sudah lunas.');
        }

        $orderId = $booking->booking_code . '-' . now()->format('YmdHis') . '-' . Str::random(6);

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
                'phone'      => $booking->user->phone,
            ],
            'item_details' => [
                [
                    'id'       => $booking->uuid,
                    'price'    => (int) $booking->total_price,
                    'quantity' => 1,
                    'name'     => 'Booking ' . $booking->booking_code,
                ],
            ],
            'callbacks' => [
                'finish' => config('app.frontend_url') . '/my-bookings',
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        // Simpan order_id ASLI (untuk matching webhook) dan snap_token ASLI (untuk re-open Snap popup)
        // Sebelumnya dua ini ketuker — snap_token diisi orderId, sekarang dibenerin.
        $booking->update([
            'midtrans_order_id' => $orderId,
            'snap_token'        => $snapToken,
        ]);

        return $snapToken;
    }

    /**
     * Proses notifikasi webhook dari Midtrans.
     * Signature key sudah diverifikasi otomatis oleh SDK di dalam class Notification.
     */
    public function handleNotification(): void
    {
        $notification = new Notification();

        $orderId = $notification->order_id;
        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status ?? null;
        $paymentType = $notification->payment_type;

        // Exact match — jangan pakai regex tebak-tebakan lagi, karena order_id
        // sekarang formatnya {booking_code}-{YmdHis}-{random}, bukan cuma 1 segmen angka.
        $booking = Booking::where('midtrans_order_id', $orderId)->first();

        if (!$booking) {
            // Booking tidak ditemukan — abaikan tapi jangan lempar error,
            // supaya Midtrans tidak retry notifikasi ini terus-menerus.
            return;
        }

        // Sudah diproses sebelumnya (webhook Midtrans bisa terkirim >1 kali untuk event yang sama)
        if ($booking->payment_status === PaymentStatus::Paid) {
            return;
        }

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                $this->markAsPaid($booking, $paymentType);
            }
            // fraudStatus 'challenge' sengaja dibiarkan pending — butuh review manual di dashboard Midtrans
        } elseif ($transactionStatus === 'settlement') {
            $this->markAsPaid($booking, $paymentType);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $booking->update(['payment_status' => PaymentStatus::Unpaid]);
        }
    }

    private function markAsPaid(Booking $booking, string $paymentType): void
    {
        $booking->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at'        => now(),
            'payment_method' => $paymentType,
            // Auto-approve — validasi ketersediaan ulang di sini sebagai jaring pengaman
            // terhadap race condition (2 booking bentrok checkout bersamaan).
            'status' => $this->hasScheduleConflict($booking)
                ? $booking->status // biarkan status lama kalau ternyata bentrok, biar admin yang putuskan manual
                : BookingStatus::Approved,
        ]);
    }

    /**
     * Cek ulang bentrok jadwal di menit-menit terakhir sebelum auto-approve.
     * Booking lain di lab yang sama, overlap waktu, dan sudah approved/ongoing.
     */
    private function hasScheduleConflict(Booking $booking): bool
    {
        return Booking::where('lab_id', $booking->lab_id)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', [BookingStatus::Approved, BookingStatus::Ongoing])
            ->where('start_time', '<', $booking->end_time)
            ->where('end_time', '>', $booking->start_time)
            ->exists();
    }
}