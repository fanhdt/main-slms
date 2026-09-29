<?php

declare(strict_types=1);

namespace App\Domain\LabService\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'description'   => $this->description,
            'price_type'    => $this->price_type ? [
                'value' => $this->price_type->value,
                'label' => $this->price_type->label(),
            ] : null,
            'price'         => $this->price,
            'extra_minutes' => $this->extra_minutes,
            'order'         => $this->order,
            'is_active'     => $this->is_active,
        ];
    }
}