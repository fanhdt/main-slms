<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

/**
 * UserRole Enum.
 *
 * Mendefinisikan semua role yang ada di SLMS.
 * Gunakan enum ini (bukan string literal) di seluruh codebase
 * untuk menghindari typo dan memudahkan refactoring.
 */
enum UserRole: string
{
    case SuperAdmin  = 'super_admin';
    case LabAdmin    = 'lab_admin';
    case Operator    = 'operator';
    case Photographer = 'photographer';
    case Editor      = 'editor';
    case Customer    = 'customer';
    case Guest       = 'guest';

    /**
     * Label yang ditampilkan ke user.
     */
    public function label(): string
    {
        return match($this) {
            self::SuperAdmin   => 'Super Admin',
            self::LabAdmin     => 'Lab Admin',
            self::Operator     => 'Operator',
            self::Photographer => 'Photographer',
            self::Editor       => 'Editor',
            self::Customer     => 'Customer',
            self::Guest        => 'Guest',
        };
    }

    /**
     * Role yang dapat mengelola lab (admin-level).
     */
    public static function adminRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::LabAdmin->value,
        ];
    }

    /**
     * Role yang termasuk staff lab.
     */
    public static function staffRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::LabAdmin->value,
            self::Operator->value,
            self::Photographer->value,
            self::Editor->value,
        ];
    }
}
