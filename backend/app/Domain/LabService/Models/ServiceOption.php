<?php

declare(strict_types=1);

namespace App\Domain\LabService\Models;

use App\Core\Traits\HasUuid;
use App\Domain\LabService\Enums\ServiceOptionPriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOption extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'service_id',
        'name',
        'description',
        'price_type',
        'price',
        'extra_minutes',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_type'    => ServiceOptionPriceType::class,
            'price'         => 'decimal:2',
            'extra_minutes' => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}