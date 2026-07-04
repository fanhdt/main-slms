<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum BookingPurpose: string
{
    case Academic     = 'academic';     // Mahasiswa, untuk keperluan kuliah — gratis
    case Organization = 'organization'; // Mahasiswa, himpunan/mandiri di luar jam kuliah — diskon
    case Public       = 'public';       // Umum — tarif penuh

    public function label(): string
    {
        return match($this) {
            self::Academic     => 'Akademik / Perkuliahan',
            self::Organization => 'Organisasi / Mandiri (Mahasiswa)',
            self::Public       => 'Umum',
        };
    }

    /**
     * Purpose ini butuh user terverifikasi sebagai mahasiswa (punya NIM)?
     */
    public function requiresStudent(): bool
    {
        return $this !== self::Public;
    }
}