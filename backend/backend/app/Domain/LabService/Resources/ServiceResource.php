<?php

declare(strict_types=1);

namespace App\Domain\LabService\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\LabService\Models\ServiceOption;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
{
    return [
        'id'                => $this->id,
        'uuid'              => $this->uuid,
        'lab_id'            => $this->lab?->uuid,
        'name'              => $this->name,
        'type'              => [
            'value' => $this->type->value,
            'label' => $this->type->label(),
        ],
        'description'       => $this->description,
        'pricing_type'      => $this->pricing_type ? [
            'value' => $this->pricing_type->value,
            'label' => $this->pricing_type->label(),
        ] : null,
        'price'             => $this->price,
        'duration'          => $this->duration,
        'min_quantity'      => $this->min_quantity,
        'max_quantity'      => $this->max_quantity,
        'includes'          => $this->includes,
        'image'             => $this->image_url,
        'is_active'         => $this->is_active,
        'is_custom_pricing' => $this->is_custom_pricing, // NEW
        'created_at'        => $this->created_at->toISOString(),
        'requires_schedule' => $this->type->requiresSchedule(),
        'options'           => ServiceOptionResource::collection($this->whenLoaded('options')),
    ];
}
}