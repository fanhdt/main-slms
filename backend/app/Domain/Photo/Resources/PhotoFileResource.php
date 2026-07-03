<?php

declare(strict_types=1);

namespace App\Domain\Photo\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'type'        => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'filename'    => $this->filename,
            // URL sementara (60 menit) supaya file di MinIO tidak public,
            // frontend selalu minta resource ini ulang kalau URL kadaluarsa.
            'url'         => $this->getTemporaryUrl(),
            'size'        => $this->size,
            'mime_type'   => $this->mime_type,
            'is_selected' => $this->is_selected,
            'order'       => $this->order,
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}