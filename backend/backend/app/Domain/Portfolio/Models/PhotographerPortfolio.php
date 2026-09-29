<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasImageUrl;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PhotographerPortfolio Model.
 *
 * Foto portofolio milik seorang fotografer di sebuah lab fotografi.
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $lab_id
 * @property int    $photographer_id
 * @property string $image
 * @property string|null $caption
 * @property int    $order
 */
class PhotographerPortfolio extends Model
{
    use BelongsToLab;
    use HasImageUrl;
    use HasUuid;
    use LogsActivity;

    public const IMAGE_DISK = 's3';

    protected $fillable = [
        'uuid',
        'lab_id',
        'photographer_id',
        'image',
        'disk',
        'caption',
        'order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['photographer_id', 'caption', 'order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('portfolio');
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(Photographer::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->image);
    }
}