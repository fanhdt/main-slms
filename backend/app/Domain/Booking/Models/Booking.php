<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasUuid;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Photo\Models\PhotoProject;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use BelongsToLab;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'lab_id',
        'user_id',
        'booking_code',
        'start_time',
        'end_time',
        'status',
        'payment_status',
        'checked_in_at',
        'total_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time'     => 'datetime',
            'end_time'       => 'datetime',
            'checked_in_at'  => 'datetime',
            'status'         => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'total_price'    => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(BookingAsset::class);
    }

    public function photoProject(): HasOne
    {
        return $this->hasOne(PhotoProject::class, 'booking_id');
    }
}