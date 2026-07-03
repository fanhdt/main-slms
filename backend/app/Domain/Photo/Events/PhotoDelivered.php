<?php

declare(strict_types=1);

namespace App\Domain\Photo\Events;

use App\Domain\Photo\Models\PhotoProject;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dipancarkan saat customer approve hasil edit dan foto final siap didownload.
 * Didengar oleh customer: "foto siap didownload".
 */
class PhotoDelivered implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PhotoProject $project,
    ) {}

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->project->booking->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'photo.delivered';
    }

    public function broadcastWith(): array
    {
        return [
            'project_uuid' => $this->project->uuid,
            'expires_at'   => $this->project->expires_at?->toISOString(),
        ];
    }
}
