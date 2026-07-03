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
 * Dipancarkan saat status booking berubah (approved/rejected/completed/dll).
 * Didengar oleh customer pemilik booking lewat channel personal App.Models.User.{id}.
 */
class BookingStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Booking $booking,
        public readonly string $previousStatus,
    ) {}

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->booking->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_uuid'    => $this->booking->uuid,
            'booking_code'    => $this->booking->booking_code,
            'previous_status' => $this->previousStatus,
            'status'          => $this->booking->status->value,
            'status_label'    => $this->booking->status->label(),
        ];
    }
}
