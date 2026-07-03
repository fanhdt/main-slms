<?php

declare(strict_types=1);

namespace App\Domain\LabService\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'lab_id'      => $this->lab?->uuid,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'discount'    => $this->discount,
            'final_price' => $this->final_price,
            'duration'    => $this->duration,
            'includes'    => $this->includes,
            'addons'      => $this->addons,
            'image'       => $this->image,
            'is_active'   => $this->is_active,
            'is_custom'   => $this->is_custom,
            'items'       => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id'       => $item->id,
                    'service'  => new ServiceResource($item->service),
                    'quantity' => $item->quantity,
                    'notes'    => $item->notes,
                ]);
            }),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}