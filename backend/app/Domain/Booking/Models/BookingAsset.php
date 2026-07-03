<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAsset extends Model
{
    protected $fillable = [
        'booking_id',
        'asset_id',
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
}