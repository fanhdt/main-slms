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
    case SuperAdmin   = 'super_admin';
    case LabAdmin     = 'lab_admin';
    case Operator     = 'operator';
    case Photographer = 'photographer';
    case Editor       = 'editor';
    case Customer     = 'customer';
    case Guest        = 'guest';

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
     * Deskripsi tugas & kewenangan tiap role.
     * Ditampilkan di form user management supaya admin paham
     * konsekuensi memilih role tertentu untuk seseorang.
     */
    public function description(): string
    {
        return match($this) {
            self::SuperAdmin => 'Akses penuh ke seluruh sistem dan semua laboratorium. '
                . 'TIDAK BISA diberikan lewat aplikasi — hanya bisa di-set langsung oleh '
                . 'developer/administrator server lewat CLI (artisan), demi keamanan.',
            self::LabAdmin => 'Mengelola satu laboratorium secara penuh: booking, aset, '
                . 'layanan, paket, portofolio, dan pengaturan lab. Tidak berwenang '
                . 'mengelola akun/role pengguna secara global, dan hanya boleh menghapus '
                . 'akun customer bermasalah di labnya sendiri.',
            self::Operator => 'Menjalankan operasional harian lab: menerima dan memproses '
                . 'booking, memantau ketersediaan aset, melakukan check-in pelanggan, '
                . 'dan melihat laporan penggunaan.',
            self::Photographer => 'Bertugas pada sesi pemotretan: mengunggah foto preview '
                . 'hasil jepretan dan melihat jadwal booking yang berkaitan dengannya.',
            self::Editor => 'Mengunggah hasil edit foto, menindaklanjuti revisi dari '
                . 'customer, dan menyiapkan file final untuk pengiriman.',
            self::Customer => 'Pengguna umum aplikasi: membuat booking, melihat riwayat '
                . 'booking miliknya, memilih foto favorit, dan mengunduh hasil foto.',
            self::Guest => 'Hanya bisa melihat daftar layanan dan paket yang bersifat '
                . 'publik, tanpa bisa membuat booking.',
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

    /**
     * Role yang BOLEH diberikan lewat aplikasi (create/update/assign-role).
     * super_admin sengaja dikecualikan — hanya bisa di-set lewat CLI langsung
     * ke database, supaya tidak ada jalur di aplikasi yang bisa menaikkan
     * seseorang jadi super_admin (mencegah privilege escalation).
     */
    public static function assignableRoles(): array
    {
        return array_values(array_diff(
            array_map(fn (self $r) => $r->value, self::cases()),
            [self::SuperAdmin->value]
        ));
    }
}