<?php

declare(strict_types=1);

namespace App\Domain\Booking\Events;

use App\Domain\Booking\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dipancarkan saat booking di-checkin (scan QR).
 * Didengar oleh staff lab lain lewat channel Lab.{labId}, supaya
 * kalau checkin dilakukan dari device berbeda, admin lain tetap update.
 */
class BookingCheckedIn implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('Lab.' . $this->booking->lab_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.checked-in';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_uuid'  => $this->booking->uuid,
            'booking_code'  => $this->booking->booking_code,
            'checked_in_at' => $this->booking->checked_in_at?->toISOString(),
        ];
    }
}
