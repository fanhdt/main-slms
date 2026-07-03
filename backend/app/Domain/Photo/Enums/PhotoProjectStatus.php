<?php

declare(strict_types=1);

namespace App\Domain\Photo\Enums;

enum PhotoProjectStatus: string
{
    case Pending          = 'pending';
    case PreviewUploaded  = 'preview_uploaded';
    case Selection        = 'selection';
    case Editing          = 'editing';
    case Approval         = 'approval';
    case Delivered        = 'delivered';
    case Expired          = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Pending         => 'Menunggu Preview',
            self::PreviewUploaded => 'Preview Tersedia',
            self::Selection       => 'Menunggu Pilihan Customer',
            self::Editing         => 'Sedang Diedit',
            self::Approval        => 'Menunggu Persetujuan',
            self::Delivered       => 'Siap Download',
            self::Expired         => 'Kadaluarsa',
        };
    }

    /**
     * Status berikutnya dalam workflow.
     */
    public function next(): ?self
    {
        return match($this) {
            self::Pending         => self::PreviewUploaded,
            self::PreviewUploaded => self::Selection,
            self::Selection       => self::Editing,
            self::Editing         => self::Approval,
            self::Approval        => self::Delivered,
            self::Delivered       => self::Expired,
            self::Expired         => null,
        };
    }
}