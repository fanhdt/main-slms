<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum BookingType: string
{
    case LabRental   = 'lab_rental';   // Pinjam lab, sesuai jadwal, tanpa item
    case AssetRental  = 'asset_rental'; // Sewa properti/alat (kamera dll)
    case Service      = 'service';      // Jasa (fotografi/editing/dll) + paket

    public function label(): string
    {
        return match($this) {
            self::LabRental  => 'Pinjam Lab',
            self::AssetRental => 'Sewa Alat',
            self::Service     => 'Jasa & Paket',
        };
    }
}