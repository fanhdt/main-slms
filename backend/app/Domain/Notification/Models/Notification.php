<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use App\Core\Traits\HasUuid;
use App\Domain\Lab\Models\Lab;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id', 'lab_id', 'type', 'title', 'body', 'data', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
