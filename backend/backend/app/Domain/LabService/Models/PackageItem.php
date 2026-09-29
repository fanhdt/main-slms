<?php

declare(strict_types=1);

namespace App\Domain\LabService\Models;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItem extends Model
{
    protected $fillable = [
        'package_id',
        'service_id',
        'asset_id',
        'quantity',
        'duration_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function getTypeAttribute(): string
    {
        return $this->asset_id ? 'asset' : 'service';
    }
}