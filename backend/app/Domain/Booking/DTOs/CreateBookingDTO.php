<?php

namespace App\Domain\Booking\DTOs;

use App\Domain\Lab\Models\Lab;
use App\Domain\LabService\Models\Service;
use App\Domain\LabService\Models\Package;

readonly class CreateBookingDTO
{
    public function __construct(
        public int    $labId,
        public int    $userId,
        public string $startTime,
        public string $endTime,
        public array  $items,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        // Translate lab_uuid → lab_id (integer)
        $labId = Lab::where('uuid', $data['lab_uuid'])->value('id');

        // Translate setiap item dari uuid ke id
        $items = collect($data['items'])->map(function ($item) {
            $resolved = [
                'quantity' => $item['quantity'],
            ];

            if (!empty($item['service_uuid'])) {
                $resolved['service_id'] = Service::where('uuid', $item['service_uuid'])->value('id');
            }

            if (!empty($item['package_uuid'])) {
                $resolved['package_id'] = Package::where('uuid', $item['package_uuid'])->value('id');
            }

            return $resolved;
        })->toArray();

        return new self(
            labId:     $labId,
            userId:    $userId,
            startTime: $data['start_time'],
            endTime:   $data['end_time'],
            items:     $items,
            notes:     $data['notes'] ?? null,
        );
    }
}