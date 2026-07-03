<?php

declare(strict_types=1);

namespace App\Domain\LabService\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'lab_id'       => $this->lab?->uuid,
            'name'         => $this->name,
            'type'         => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'description'  => $this->description,
            'pricing_type' => [
                'value' => $this->pricing_type->value,
                'label' => $this->pricing_type->label(),
            ],
            'price'        => $this->price,
            'duration'     => $this->duration,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'includes'     => $this->includes,
            'image'        => $this->image,
            'is_active'    => $this->is_active,
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}