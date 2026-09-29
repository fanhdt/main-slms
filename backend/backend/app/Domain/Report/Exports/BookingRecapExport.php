<?php

declare(strict_types=1);

namespace App\Domain\Report\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BookingRecapExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $rows,
        private readonly string $title,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Kode Booking', 'Nama Customer', 'NIM', 'Tipe', 'Status', 'Pembayaran', 'Total Harga', 'Waktu Dibuat'];
    }

    public function title(): string
    {
        return $this->title;
    }
}