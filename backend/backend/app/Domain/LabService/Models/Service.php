<?php

declare(strict_types=1);

namespace App\Domain\LabService\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasImageUrl;
use App\Core\Traits\HasUuid;
use App\Domain\LabService\Enums\PricingType;
use App\Domain\LabService\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use BelongsToLab;
    use HasFactory;
    use HasImageUrl; // NEW
    use HasUuid;
    use SoftDeletes;

    public const IMAGE_DISK = 's3'; // NEW

    protected $fillable = [
    'uuid',
    'lab_id',
    'name',
    'type',
    'description',
    'pricing_type',
    'price',
    'duration',
    'min_quantity',
    'max_quantity',
    'includes',
    'image',
    'is_active',
    'is_custom_pricing', 
];

protected function casts(): array
{
    return [
        'type'              => ServiceType::class,
        'pricing_type'      => PricingType::class,
        'includes'          => 'array',
        'price'             => 'decimal:2',
        'is_active'         => 'boolean',
        'is_custom_pricing' => 'boolean', 
    ];
}

    public function packageItems(): HasMany
    {
        return $this->hasMany(PackageItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // NEW
    public function getImageUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->image);
    }

    public function options(): HasMany
{
    return $this->hasMany(ServiceOption::class);
}
}