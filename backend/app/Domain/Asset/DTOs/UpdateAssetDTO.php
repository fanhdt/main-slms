<?php

declare(strict_types=1);

namespace App\Domain\Asset\DTOs;

readonly class UpdateAssetDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $category = null,
        public ?string $brand = null,
        public ?string $model = null,
        public ?string $description = null,
        public ?string $serialNumber = null,
        public ?string $status = null,
        public ?float  $purchasePrice = null,
        public ?string $purchaseDate = null,
        public ?array  $specifications = null,
        public ?string $image = null,
        public ?bool   $isRentable = null,
        public ?float  $rentalPrice = null,
        public ?int $quantity = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
        name:           $data['name'] ?? null,
        category:       $data['category'] ?? null,
        brand:          $data['brand'] ?? null,
        model:          $data['model'] ?? null,
        description:    $data['description'] ?? null,
        serialNumber:   $data['serial_number'] ?? null,
        status:         $data['status'] ?? null,
        purchasePrice:  isset($data['purchase_price']) ? (float) $data['purchase_price'] : null,
        purchaseDate:   $data['purchase_date'] ?? null,
        specifications: $data['specifications'] ?? null,
        image:          $data['image'] ?? null,
        isRentable:     $data['is_rentable'] ?? null,
        rentalPrice:    isset($data['rental_price']) ? (float) $data['rental_price'] : null,
        quantity: isset($data['quantity']) ? (int) $data['quantity'] : null,
    );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'           => $this->name,
            'category'       => $this->category,
            'brand'          => $this->brand,
            'model'          => $this->model,
            'description'    => $this->description,
            'serial_number'  => $this->serialNumber,
            'status'         => $this->status,
            'purchase_price' => $this->purchasePrice,
            'purchase_date'  => $this->purchaseDate,
            'specifications' => $this->specifications,
            'image'          => $this->image,
            'is_rentable'    => $this->isRentable,
            'rental_price'   => $this->rentalPrice,
            'quantity' => $this->quantity,
        ], fn ($value) => $value !== null);
    }
}