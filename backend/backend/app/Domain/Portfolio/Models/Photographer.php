<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasImageUrl;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Photographer extends Model
{
    use BelongsToLab;
    use HasImageUrl;
    use HasUuid;

    public const IMAGE_DISK = 's3';

    protected $fillable = [
        'uuid', 'lab_id', 'name', 'photo', 'bio', 'instagram', 'order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function portfolios(): HasMany
    {
        return $this->hasMany(PhotographerPortfolio::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->photo);
    }
}