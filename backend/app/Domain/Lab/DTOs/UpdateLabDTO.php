<?php

declare(strict_types=1);

namespace App\Domain\Lab\DTOs;

readonly class UpdateLabDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $primaryColor = null,
        public ?string $secondaryColor = null,
        public ?string $logo = null,
        public ?string $heroImage = null,
        public ?string $favicon = null,
        public ?array  $contact = null,
        public ?array  $settings = null,
        public ?bool   $isActive = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name:           $data['name'] ?? null,
            description:    $data['description'] ?? null,
            primaryColor:   $data['primary_color'] ?? null,
            secondaryColor: $data['secondary_color'] ?? null,
            logo:           $data['logo'] ?? null,
            heroImage:      $data['hero_image'] ?? null,
            favicon:        $data['favicon'] ?? null,
            contact:        $data['contact'] ?? null,
            settings:       $data['settings'] ?? null,
            isActive:       $data['is_active'] ?? null,
        );
    }

    /**
     * Konversi DTO ke array untuk update model.
     * Hanya field yang tidak null yang dimasukkan
     * agar tidak menimpa data yang tidak dikirim.
     */
    public function toArray(): array
    {
        return array_filter([
            'name'            => $this->name,
            'description'     => $this->description,
            'primary_color'   => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'logo'            => $this->logo,
            'hero_image'      => $this->heroImage,
            'favicon'         => $this->favicon,
            'contact'         => $this->contact,
            'settings'        => $this->settings,
            'is_active'       => $this->isActive,
        ], fn ($value) => $value !== null);
    }
}