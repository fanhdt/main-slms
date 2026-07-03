<?php

declare(strict_types=1);

namespace App\Domain\Booking\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'booking_code'   => $this->booking_code,
            'lab_id'         => $this->lab?->uuid,
            'user'           => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user?->name,
            ],
            'start_time'     => $this->start_time->toISOString(),
            'end_time'       => $this->end_time->toISOString(),
            'status'         => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'payment_status' => [
                'value' => $this->payment_status->value,
                'label' => $this->payment_status->label(),
            ],
            'checked_in_at'  => $this->checked_in_at?->toISOString(),
            'total_price'    => $this->total_price,
            'notes'          => $this->notes,

            'items'          => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id'         => $item->id,
                    'service_id' => $item->service_id,
                    'package_id' => $item->package_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'subtotal'   => $item->subtotal,
                ]);
            }),

            'assets' => $this->whenLoaded('assets', function () {
                return $this->assets->map(fn ($bookingAsset) => [
                    'id'           => $bookingAsset->id,
                    'asset_id'     => $bookingAsset->asset_id,
                    'status'       => $bookingAsset->status,
                    'return_notes' => $bookingAsset->return_notes,
                ]);
            }),

            // Ringkasan status photo project (kalau ada), dipakai frontend
            // untuk menentukan tombol "Pilih Foto" / "Lihat Hasil Foto" di MyBookingsPage.
            'photo_project' => $this->whenLoaded('photoProject', function () {
                if (!$this->photoProject) {
                    return null;
                }

                return [
                    'uuid'   => $this->photoProject->uuid,
                    'status' => [
                        'value' => $this->photoProject->status->value,
                        'label' => $this->photoProject->status->label(),
                    ],
                ];
            }),

            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}