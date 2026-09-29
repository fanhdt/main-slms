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
 * Dipancarkan saat customer submit foto pilihan.
 * Didengar oleh staff lab (editor/photographer) lewat channel Lab.{labId}.
 */
class PhotoSelectionSubmitted implements ShouldBroadcast
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
            new PrivateChannel('Lab.' . $this->project->lab_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'photo.selection-submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'project_uuid'    => $this->project->uuid,
            'selection_count' => $this->project->selection_count,
        ];
    }
}
