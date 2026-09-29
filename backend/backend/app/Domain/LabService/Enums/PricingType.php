<?php

declare(strict_types=1);

namespace App\Domain\LabService\Enums;

enum PricingType: string
{
    case PerSession  = 'per_session';  // per sesi booking
    case PerHour     = 'per_hour';     // per jam
    case PerDay      = 'per_day';      // per hari
    case PerUnit     = 'per_unit';     // per unit/item
    case PerPerson   = 'per_person';   // per orang
    case PerPhoto    = 'per_photo';    // per lembar foto
    case Fixed       = 'fixed';        // harga tetap

    public function label(): string
    {
        return match($this) {
            self::PerSession => 'Per Sesi',
            self::PerHour    => 'Per Jam',
            self::PerDay     => 'Per Hari',
            self::PerUnit    => 'Per Unit',
            self::PerPerson  => 'Per Orang',
            self::PerPhoto   => 'Per Foto',
            self::Fixed      => 'Harga Tetap',
        };
    }
}