<?php

declare(strict_types=1);

namespace App\Domain\Asset\DTOs;

readonly class CreateAssetDTO
{
    public function __construct(
        public int     $labId,
        public string  $name,
        public string  $code,
        public string  $category,
        public ?string $brand = null,
        public ?string $model = null,
        public ?string $description = null,
        public ?string $serialNumber = null,
        public string  $status = 'available',
        public ?float  $purchasePrice = null,
        public ?string $purchaseDate = null,
        public ?array  $specifications = null,
        public ?string $image = null,
        public bool    $isRentable = true,
        public ?float  $rentalPrice = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            labId:          $data['lab_id'],
            name:           $data['name'],
            code:           $data['code'],
            category:       $data['category'],
            brand:          $data['brand'] ?? null,
            model:          $data['model'] ?? null,
            description:    $data['description'] ?? null,
            serialNumber:   $data['serial_number'] ?? null,
            status:         $data['status'] ?? 'available',
            purchasePrice:  $data['purchase_price'] ?? null,
            purchaseDate:   $data['purchase_date'] ?? null,
            specifications: $data['specifications'] ?? null,
            image:          $data['image'] ?? null,
            isRentable:     $data['is_rentable'] ?? true,
            rentalPrice:    $data['rental_price'] ?? null,
        );
    }
}