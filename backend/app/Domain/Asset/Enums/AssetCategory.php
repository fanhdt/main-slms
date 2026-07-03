<?php

declare(strict_types=1);

namespace App\Domain\Asset\Enums;

enum AssetCategory: string
{
    case Camera     = 'camera';
    case Lens       = 'lens';
    case Lighting   = 'lighting';
    case Drone      = 'drone';
    case Tripod     = 'tripod';
    case Computer   = 'computer';
    case Projector  = 'projector';
    case Audio      = 'audio';
    case Microphone = 'microphone';
    case Printer    = 'printer';
    case Other      = 'other';

    public function label(): string
    {
        return match($this) {
            self::Camera     => 'Kamera',
            self::Lens       => 'Lensa',
            self::Lighting   => 'Lighting',
            self::Drone      => 'Drone',
            self::Tripod     => 'Tripod',
            self::Computer   => 'Komputer',
            self::Projector  => 'Proyektor',
            self::Audio      => 'Audio',
            self::Microphone => 'Mikrofon',
            self::Printer    => 'Printer',
            self::Other      => 'Lainnya',
        };
    }
}