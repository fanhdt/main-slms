<?php

declare(strict_types=1);

namespace App\Domain\Asset\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasImageUrl;
use App\Core\Traits\HasUuid;
use App\Domain\Asset\Enums\AssetCategory;
use App\Domain\Asset\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
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
        'code',
        'category',
        'brand',
        'model',
        'description',
        'serial_number',
        'status',
        'purchase_price',
        'purchase_date',
        'specifications',
        'image',
        'is_rentable',
        'rental_price',
        // FIX: kolom ini sudah ada di migration & sudah dikirim dari
        // CreateAssetDTO/UpdateAssetDTO, tapi tidak pernah masuk ke $fillable
        // sehingga Eloquent mass-assignment protection selalu diam-diam
        // menolaknya — akibatnya quantity selalu tersimpan sebagai default (1)
        // berapa pun angka yang diinput di form edit.
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'category'       => AssetCategory::class,
            'status'         => AssetStatus::class,
            'specifications' => 'array',
            'is_rentable'    => 'boolean',
            'purchase_price' => 'decimal:2',
            'rental_price'   => 'decimal:2',
            'purchase_date'  => 'date',
            'quantity'       => 'integer',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->image);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', AssetStatus::Available);
    }

    public function scopeRentable($query)
    {
        return $query->where('is_rentable', true);
    }
}