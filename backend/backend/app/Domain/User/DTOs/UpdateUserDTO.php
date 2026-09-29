<?php

declare(strict_types=1);

namespace App\Domain\User\DTOs;

readonly class UpdateUserDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $password = null,
        public ?string $role = null,
        public ?bool   $isActive = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name:     $data['name'] ?? null,
            phone:    $data['phone'] ?? null,
            password: $data['password'] ?? null,
            role:     $data['role'] ?? null,
            isActive: $data['is_active'] ?? null,
        );
    }

    /**
     * Konversi ke array untuk update model.
     * Hanya field yang tidak null yang dimasukkan.
     */
    public function toArray(): array
    {
        return array_filter([
            'name'      => $this->name,
            'phone'     => $this->phone,
            'password'  => $this->password,
            'is_active' => $this->isActive,
        ], fn ($value) => $value !== null);
    }
}