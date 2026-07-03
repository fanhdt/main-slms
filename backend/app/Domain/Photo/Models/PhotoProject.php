<?php

declare(strict_types=1);

namespace App\Domain\Photo\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasUuid;
use App\Domain\Booking\Models\Booking;
use App\Domain\Photo\Enums\PhotoProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhotoProject extends Model
{
    use BelongsToLab;
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'booking_id',
        'lab_id',
        'status',
        'preview_count',
        'selection_count',
        'max_selection',
        'notes',
        'customer_note',
        'editor_note',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status'     => PhotoProjectStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(PhotoFile::class, 'project_id');
    }

    public function selections(): HasMany
    {
        return $this->hasMany(PhotoSelection::class, 'project_id');
    }

    public function previews(): HasMany
    {
        return $this->hasMany(PhotoFile::class, 'project_id')
                    ->where('type', 'preview');
    }

    public function editedFiles(): HasMany
    {
        return $this->hasMany(PhotoFile::class, 'project_id')
                    ->where('type', 'edited');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}