<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum BookingAssetStatus: string
{
    case Reserved = 'reserved';
    case Borrowed = 'borrowed';
    case Returned = 'returned';
    case Damaged  = 'damaged';

    public function label(): string
    {
        return match($this) {
            self::Reserved => 'Dipesan',
            self::Borrowed => 'Sedang Dipinjam',
            self::Returned => 'Sudah Dikembalikan',
            self::Damaged  => 'Rusak/Hilang',
        };
    }
}