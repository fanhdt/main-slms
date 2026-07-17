<?php
declare(strict_types=1);
namespace App\Domain\LabService\Resources;

use App\Domain\Asset\Resources\AssetResource;
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
            'image'       => $this->image_url,
            'is_active'   => $this->is_active,
            'is_custom'   => $this->is_custom,
            'requires_schedule' => $this->whenLoaded('items', function () {
                return $this->items->contains(
                    fn ($item) => $item->service?->type->requiresSchedule() === true
                );
            }, false),
            'items'       => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id'               => $item->id,
                    'type'             => $item->type,
                    'service'          => $item->service ? new ServiceResource($item->service) : null,
                    'asset'            => $item->asset ? new AssetResource($item->asset) : null,
                    'quantity'         => $item->quantity,
                    'duration_minutes' => $item->duration_minutes,
                    'notes'            => $item->notes,
                ]);
            }),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}