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
            'id'   => $this->id,
            'uuid'           => $this->uuid,
            'booking_code'   => $this->booking_code,
            'booking_type'   => [
                'value' => $this->booking_type->value,
                'label' => $this->booking_type->label(),
            ],
            'purpose'        => $this->purpose ? [
                'value' => $this->purpose->value,
                'label' => $this->purpose->label(),
            ] : null,
            'lab_id'         => $this->lab?->uuid,
            'user'           => [
                'uuid' => $this->user?->uuid,
                'name' => $this->user?->name,
                'nim'  => $this->user?->nim,
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
                    'options'    => $item->relationLoaded('options')
                        ? $item->options->map(fn ($opt) => [
                            'id'             => $opt->id,
                            'name'           => $opt->name_snapshot,
                            'price_type'     => $opt->price_type_snapshot,
                            'price'          => $opt->price_snapshot,
                            'extra_minutes'  => $opt->extra_minutes_snapshot,
                            'subtotal'       => $opt->subtotal,
                        ])
                        : [],
                ]);
            }),

            'assets' => $this->whenLoaded('assets', function () {
    return $this->assets->map(fn ($bookingAsset) => [
        'id'           => $bookingAsset->id,
        'asset_id'     => $bookingAsset->asset_id,
        'asset_name'   => $bookingAsset->relationLoaded('asset') ? $bookingAsset->asset?->name : null,
        'rental_price' => $bookingAsset->relationLoaded('asset') ? $bookingAsset->asset?->rental_price : null,
        'quantity'     => $bookingAsset->quantity,
        'status'       => [
            'value' => $bookingAsset->status->value,
            'label' => $bookingAsset->status->label(),
        ],
        'return_notes' => $bookingAsset->return_notes,
    ]);
}),

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