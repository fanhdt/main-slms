<?php

declare(strict_types=1);

namespace App\Core\Traits;

use Illuminate\Support\Str;

/**
 * HasUuid Trait.
 *
 * Menambahkan UUID sebagai identifier publik pada model.
 * Database tetap menggunakan auto-increment ID sebagai primary key
 * untuk performa join, tapi UUID digunakan di API (tidak expose ID integer).
 *
 * Usage: tambahkan `use HasUuid;` di model dan
 * tambahkan kolom `uuid` di migration.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Cari model berdasarkan UUID.
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Cari model berdasarkan UUID atau throw ModelNotFoundException.
     */
    public static function findByUuidOrFail(string $uuid): static
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Route model binding menggunakan UUID.
     * Override getRouteKeyName() di model jika ingin binding otomatis.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
