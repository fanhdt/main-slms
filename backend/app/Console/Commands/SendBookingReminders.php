<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Kirim notifikasi reminder H-1 untuk booking yang sudah approved';

    public function handle(NotificationService $notificationService): int
    {
        // Jendela 23-25 jam ke depan — command jalan tiap jam, jendela 2 jam
        // supaya tidak ada booking yang "kelewat" gara-gara timing job vs waktu mulai.
        $windowStart = now()->addHours(23);
        $windowEnd = now()->addHours(25);

        $bookings = Booking::where('status', BookingStatus::Approved)
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->with('user')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('Tidak ada booking yang perlu diingatkan.');
            return self::SUCCESS;
        }

        foreach ($bookings as $booking) {
            $notificationService->notifyUser(
                userId: $booking->user_id,
                type: 'BookingReminder',
                title: 'Pengingat booking besok',
                body: "Booking {$booking->booking_code} kamu dimulai " .
                    $booking->start_time->translatedFormat('l, d M Y H:i') . '.',
                data: ['booking_uuid' => $booking->uuid],
                labId: $booking->lab_id,
            );
            $booking->update(['reminder_sent_at' => now()]);
        }

        $this->info("{$bookings->count()} reminder booking terkirim.");
        return self::SUCCESS;
    }
}