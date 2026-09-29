<?php

declare(strict_types=1);

namespace App\Domain\Lab\DTOs;

readonly class CreateLabDTO
{
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $description = null,
        public ?string $primaryColor = null,
        public ?string $secondaryColor = null,
        public ?string $logo = null,
        public ?string $heroImage = null,
        public ?string $favicon = null,
        public ?array  $contact = null,
        public ?array  $settings = null,
        public bool    $isActive = true,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name:           $data['name'],
            slug:           $data['slug'],
            description:    $data['description'] ?? null,
            primaryColor:   $data['primary_color'] ?? null,
            secondaryColor: $data['secondary_color'] ?? null,
            logo:           $data['logo'] ?? null,
            heroImage:      $data['hero_image'] ?? null,
            favicon:        $data['favicon'] ?? null,
            contact:        $data['contact'] ?? null,
            settings:       $data['settings'] ?? null,
            isActive:       $data['is_active'] ?? true,
        );
    }
}