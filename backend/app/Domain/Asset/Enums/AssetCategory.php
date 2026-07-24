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
    case Backdrop   = 'backdrop';
    case Costume    = 'costume';
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
            self::Backdrop   => 'Backdrop / Properti',
            self::Costume    => 'Kostum / Aksesoris',
            self::Other      => 'Lainnya',
        };
    }

    /**
     * Ikon Lucide yang dipakai di frontend untuk kategori ini.
     * Dipetakan manual di frontend (bukan dikirim dari sini),
     * daftar ini cuma referensi biar konsisten.
     */
    public static function groupedOptions(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases()
        );
    }
}