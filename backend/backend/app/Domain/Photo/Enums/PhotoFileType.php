<?php

declare(strict_types=1);

namespace App\Domain\Photo\Enums;

enum PhotoFileType: string
{
    case Preview = 'preview';
    case Edited  = 'edited';
    case Final   = 'final';

    public function label(): string
    {
        return match($this) {
            self::Preview => 'Preview',
            self::Edited  => 'Hasil Edit',
            self::Final   => 'Final',
        };
    }
}