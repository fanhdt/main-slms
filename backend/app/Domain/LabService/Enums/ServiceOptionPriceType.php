<?php

declare(strict_types=1);

namespace App\Domain\LabService\Enums;

enum ServiceOptionPriceType: string
{
    case Flat     = 'flat';
    case PerHour  = 'per_hour';
    case PerPhoto = 'per_photo';
    case Custom   = 'custom'; 

    public function label(): string
{
    return match($this) {
        self::Flat     => 'Harga Tetap',
        self::PerHour  => 'Per Jam',
        self::PerPhoto => 'Per Foto',
        self::Custom   => 'Custom (Ditentukan Saat Booking)',
    };
}
}