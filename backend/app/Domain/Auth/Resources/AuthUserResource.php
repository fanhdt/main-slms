<?php

declare(strict_types=1);

namespace App\Domain\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar,
            'is_active'  => $this->is_active,
            'roles'      => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}