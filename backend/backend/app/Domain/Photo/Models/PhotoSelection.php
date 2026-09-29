<?php

declare(strict_types=1);

namespace App\Domain\Photo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoSelection extends Model
{
    protected $fillable = [
        'project_id',
        'photo_file_id',
        'customer_note',
        'selected_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PhotoProject::class, 'project_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PhotoFile::class, 'photo_file_id');
    }
}