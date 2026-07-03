<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public string $device = 'web',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            device: $data['device'] ?? 'web',
        );
    }
}