<?php

declare(strict_types=1);

namespace App\Domain\Photo\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'booking'          => [
                'uuid'         => $this->booking?->uuid,
                'booking_code' => $this->booking?->booking_code,
                'user'         => [
                    'uuid' => $this->booking?->user?->uuid,
                    'name' => $this->booking?->user?->name,
                ],
            ],
            'lab_id'           => $this->lab?->uuid,
            'status'           => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'preview_count'    => $this->preview_count,
            'selection_count'  => $this->selection_count,
            'max_selection'    => $this->max_selection,
            'notes'            => $this->notes,
            'customer_note'    => $this->customer_note,
            'editor_note'      => $this->editor_note,
            'expires_at'       => $this->expires_at?->toISOString(),
            'is_expired'       => $this->isExpired(),

            // Hanya dimuat kalau controller memanggil ->load('files') / ->load('previews') dll
            'previews'         => PhotoFileResource::collection($this->whenLoaded('previews')),
            'edited_files'     => PhotoFileResource::collection($this->whenLoaded('editedFiles')),
            'files'            => PhotoFileResource::collection($this->whenLoaded('files')),

            'created_at'       => $this->created_at->toISOString(),
        ];
    }
}