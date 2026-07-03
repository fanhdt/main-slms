<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $phone = '',
        public string $device = 'web',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'] ?? '',
            device: $data['device'] ?? 'web',
        );
    }
}