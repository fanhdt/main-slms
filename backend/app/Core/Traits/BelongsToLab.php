<?php

declare(strict_types=1);

namespace App\Core\Traits;

use App\Domain\Lab\Models\Lab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToLab Trait.
 *
 * Digunakan pada semua model yang memiliki lab_id.
 * Menyediakan relasi ke Lab dan scope untuk filter per lab.
 *
 * Ini adalah kunci dari arsitektur multi-lab SLMS:
 * semua data terisolasi per lab melalui lab_id.
 *
 * Usage: tambahkan `use BelongsToLab;` di model yang punya kolom lab_id.
 */
trait BelongsToLab
{
    /**
     * Relasi ke Lab.
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Scope untuk filter berdasarkan lab_id.
     *
     * Contoh penggunaan:
     * Booking::forLab(1)->paginate()
     */
    public function scopeForLab(Builder $query, int $labId): Builder
    {
        return $query->where('lab_id', $labId);
    }

    /**
     * Scope untuk filter berdasarkan lab yang aktif.
     */
    public function scopeForActiveLab(Builder $query): Builder
    {
        return $query->whereHas('lab', fn (Builder $q) => $q->where('is_active', true));
    }
}
