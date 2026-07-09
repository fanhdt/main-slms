<?php
declare(strict_types=1);
namespace App\Domain\Portfolio\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotographerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'uuid'      => $this->uuid,
            'lab_id'    => $this->lab_id,
            'name'      => $this->name,
            'photo'     => $this->photo_url,
            'bio'       => $this->bio,
            'instagram' => $this->instagram,
            'order'     => $this->order,
            'is_active' => $this->is_active,
        ];
    }
}