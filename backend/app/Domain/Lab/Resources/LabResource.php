<?php

declare(strict_types=1);

namespace App\Domain\Lab\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'is_active'       => $this->is_active,
            'branding'        => [
                'primary_color'   => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'logo'            => $this->logo,
                'hero_image'      => $this->hero_image,
                'favicon'         => $this->favicon,
            ],
            'contact'         => $this->contact,
            'settings'        => $this->settings,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}