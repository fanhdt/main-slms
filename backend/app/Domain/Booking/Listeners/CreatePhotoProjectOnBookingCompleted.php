<?php

declare(strict_types=1);

namespace App\Domain\Booking\Listeners;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Events\BookingStatusChanged;
use App\Domain\Photo\Services\PhotoDeliveryService;

class CreatePhotoProjectOnBookingCompleted
{
    public function __construct(
        private readonly PhotoDeliveryService $photoService,
    ) {}

    public function handle(BookingStatusChanged $event): void
    {
        if ($event->booking->status !== BookingStatus::Completed) {
            return;
        }

        $booking = $event->booking->load([
            'items.service',
            'items.package.items.service',
            'photoProject',
        ]);

        // Sudah pernah dibuatkan project foto — jangan duplikat
        if ($booking->photoProject) {
            return;
        }

        $hasPhotography = $booking->items->contains(function ($item) {
            if ($item->service?->type?->value === 'photography') {
                return true;
            }

            if ($item->package) {
                return $item->package->items->contains(
                    fn ($packageItem) => $packageItem->service?->type?->value === 'photography'
                );
            }

            return false;
        });

        if (!$hasPhotography) {
            return;
        }

        $this->photoService->createForBooking($booking);
    }
}