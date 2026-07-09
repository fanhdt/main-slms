<?php

declare(strict_types=1);

namespace App\Domain\Booking\Listeners;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Events\BookingStatusChanged;
use App\Domain\LabService\Models\Service;
use App\Domain\Photo\Services\PhotoDeliveryService;

class CreatePhotoProjectOnBookingCompleted
{
    /**
     * Tipe service yang berhak membuka Photo Project.
     * Sengaja termasuk photo_editing juga — supaya paket yang isinya
     * cuma jasa editing (tanpa sesi foto baru) tetap dapat alur delivery.
     */
    private const PHOTO_DELIVERY_SERVICE_TYPES = ['photography', 'photo_editing'];

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
            if ($this->isPhotoDeliveryService($item->service)) {
                return true;
            }

            if ($item->package) {
                return $item->package->items->contains(
                    fn ($packageItem) => $this->isPhotoDeliveryService($packageItem->service)
                );
            }

            return false;
        });

        if (!$hasPhotography) {
            return;
        }

        $this->photoService->createForBooking($booking);
    }

    /**
     * Cek apakah sebuah Service berhak memicu pembuatan Photo Project.
     */
    private function isPhotoDeliveryService(?Service $service): bool
    {
        return in_array($service?->type?->value, self::PHOTO_DELIVERY_SERVICE_TYPES, true);
    }
}