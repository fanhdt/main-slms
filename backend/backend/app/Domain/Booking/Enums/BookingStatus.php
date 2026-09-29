<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum BookingStatus: string
{
    case Pending   = 'pending';    // Menunggu persetujuan admin lab
    case Approved  = 'approved';   // Disetujui, siap digunakan
    case Ongoing   = 'ongoing';    // Sedang berlangsung (user sudah check-in)
    case Completed = 'completed';  // Selesai (user sudah check-out/kembalikan alat)
    case Canceled  = 'canceled';   // Dibatalkan oleh user
    case Rejected  = 'rejected';   // Ditolak oleh admin

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Menunggu Persetujuan',
            self::Approved  => 'Disetujui',
            self::Ongoing   => 'Sedang Berlangsung',
            self::Completed => 'Selesai',
            self::Canceled  => 'Dibatalkan',
            self::Rejected  => 'Ditolak',
        };
    }
}