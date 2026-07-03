<?php

declare(strict_types=1);

namespace App\Domain\Asset\Enums;

enum AssetStatus: string
{
    case Available   = 'available';
    case InUse       = 'in_use';
    case Maintenance = 'maintenance';
    case Retired     = 'retired';

    public function label(): string
    {
        return match($this) {
            self::Available   => 'Tersedia',
            self::InUse       => 'Sedang Dipakai',
            self::Maintenance => 'Dalam Perbaikan',
            self::Retired     => 'Tidak Aktif',
        };
    }

    /**
     * Hanya status ini yang bisa dibooking.
     */
    public function isBookable(): bool
    {
        return $this === self::Available;
    }
}