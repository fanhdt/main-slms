<?php

namespace App\Domain\Booking\DTOs;

use App\Domain\Asset\Models\Asset;
use App\Domain\Booking\Enums\BookingType;
use App\Domain\Lab\Models\Lab;
use App\Domain\LabService\Models\Service;
use App\Domain\LabService\Models\Package;

readonly class CreateBookingDTO
{
    public function __construct(
        public int $labId,
        public int $userId,
        public string $startTime,
        public string $endTime,
        public BookingType $bookingType,
        public array $items = [],
        public array $assetIds = [],
        public ?string $purpose = null,
        public ?string $nim = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        $labId = Lab::where('uuid', $data['lab_uuid'])->value('id');
        $bookingType = BookingType::from($data['booking_type']);

        $items = collect($data['items'] ?? [])->map(function ($item) {
            $resolved = ['quantity' => $item['quantity']];

            if (!empty($item['service_uuid'])) {
                $resolved['service_id'] = Service::where('uuid', $item['service_uuid'])->value('id');
            }
            if (!empty($item['package_uuid'])) {
                $resolved['package_id'] = Package::where('uuid', $item['package_uuid'])->value('id');
            }

            return $resolved;
        })->toArray();

        $assetIds = collect($data['asset_uuids'] ?? [])
            ->map(fn ($uuid) => Asset::where('uuid', $uuid)->value('id'))
            ->filter()
            ->values()
            ->toArray();

        return new self(
            labId:       $labId,
            userId:      $userId,
            startTime:   $data['start_time'],
            endTime:     $data['end_time'],
            bookingType: $bookingType,
            items:       $items,
            assetIds:    $assetIds,
            purpose:     $data['purpose'] ?? null,
            nim:         $data['nim'] ?? null,
            notes:       $data['notes'] ?? null,
        );
    }
}