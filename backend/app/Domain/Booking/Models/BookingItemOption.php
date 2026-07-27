<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Domain\LabService\Models\ServiceOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItemOption extends Model
{
    protected $fillable = [
        'booking_item_id',
        'service_option_id',
        'name_snapshot',
        'price_type_snapshot',
        'price_snapshot',
        'extra_minutes_snapshot',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'price_snapshot'         => 'decimal:2',
            'subtotal'               => 'decimal:2',
            'extra_minutes_snapshot' => 'integer',
        ];
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function serviceOption(): BelongsTo
    {
        return $this->belongsTo(ServiceOption::class);
    }
}