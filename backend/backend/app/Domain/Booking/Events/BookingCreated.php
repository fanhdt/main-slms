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
 * Dipancarkan saat customer membuat booking baru.
 * Didengar oleh admin/operator lab lewat channel Lab.{labId}.
 */
class BookingCreated implements ShouldBroadcast
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
        return 'booking.created';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_uuid' => $this->booking->uuid,
            'booking_code' => $this->booking->booking_code,
            'user_name'    => $this->booking->user?->name,
            'status'       => $this->booking->status->value,
            'start_time'   => $this->booking->start_time->toISOString(),
        ];
    }
}
