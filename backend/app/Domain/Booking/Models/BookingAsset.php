<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAsset extends Model
{
    protected $fillable = [
    'asset_id',
    'quantity',
    'rental_days',
    'subtotal',
    'status',
    'return_notes',
];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    protected function casts(): array
{
    return [
        'status' => \App\Domain\Booking\Enums\BookingAssetStatus::class,
    ];
}
}