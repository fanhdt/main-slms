<?php

declare(strict_types=1);

namespace App\Domain\Asset\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'lab_id'         => $this->lab?->uuid,
            'name'           => $this->name,
            'code'           => $this->code,
            'category'       => [
                'value' => $this->category->value,
                'label' => $this->category->label(),
            ],
            'brand'          => $this->brand,
            'model'          => $this->model,
            'description'    => $this->description,
            'serial_number'  => $this->serial_number,
            'status'         => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'specifications' => $this->specifications,
            'image'          => $this->image,
            'is_rentable'    => $this->is_rentable,
            'rental_price'   => $this->rental_price,
            'purchase_price' => $this->purchase_price,
            'purchase_date'  => $this->purchase_date?->toDateString(),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}