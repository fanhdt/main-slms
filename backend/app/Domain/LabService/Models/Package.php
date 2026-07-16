<?php
declare(strict_types=1);
namespace App\Domain\LabService\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasImageUrl;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use BelongsToLab;
    use HasFactory;
    use HasImageUrl;
    use HasUuid;
    use SoftDeletes;

    public const IMAGE_DISK = 's3';

    protected $fillable = [
        'uuid',
        'lab_id',
        'name',
        'description',
        'price',
        'discount',
        'duration',
        'includes',
        'addons',
        'image',
        'is_active',
        'is_custom',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'discount'  => 'decimal:2',
            'includes'  => 'array',
            'addons'    => 'array',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class);
    }

    public function getFinalPriceAttribute(): float
    {
        if ($this->discount > 0) {
            return (float) $this->price * (1 - $this->discount / 100);
        }
        return (float) $this->price;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}