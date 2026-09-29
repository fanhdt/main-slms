<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum PaymentStatus: string
{
    case Unpaid   = 'unpaid';      // Belum ada pembayaran sama sekali
    case Partial  = 'partial';     // Baru bayar DP (Down Payment) atau sebagian
    case Paid     = 'paid';        // Lunas
    case Refunded = 'refunded';    // Uang dikembalikan (misal batal)

    public function label(): string
    {
        return match($this) {
            self::Unpaid   => 'Belum Dibayar',
            self::Partial  => 'Dibayar Sebagian (DP)',
            self::Paid     => 'Lunas',
            self::Refunded => 'Dikembalikan',
        };
    }
}