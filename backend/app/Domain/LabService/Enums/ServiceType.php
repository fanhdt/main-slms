<?php

declare(strict_types=1);

namespace App\Domain\LabService\Enums;

enum ServiceType: string
{
    case Photography      = 'photography';
    case PhotoEditing     = 'photo_editing';
    case StudioRental     = 'studio_rental';
    case EquipmentRental  = 'equipment_rental';
    case Recording        = 'recording';
    case Training         = 'training';
    case Printing         = 'printing';
    case Other            = 'other';

    public function label(): string
    {
        return match($this) {
            self::Photography     => 'Fotografi',
            self::PhotoEditing    => 'Editing Foto',
            self::StudioRental    => 'Sewa Studio',
            self::EquipmentRental => 'Sewa Peralatan',
            self::Recording       => 'Recording',
            self::Training        => 'Pelatihan',
            self::Printing        => 'Cetak Foto',
            self::Other           => 'Lainnya',
        };
    }
}