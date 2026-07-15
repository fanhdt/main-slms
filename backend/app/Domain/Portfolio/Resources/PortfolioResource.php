<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'lab_id'            => $this->lab_id,
            'photographer_name' => $this->photographer?->name,
            'image'             => $this->image_url,
            'caption'           => $this->caption,
            'order'             => $this->order,
            'created_at'        => $this->created_at->toISOString(),
        ];
    }
}