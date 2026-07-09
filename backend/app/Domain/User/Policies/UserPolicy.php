<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;

class UserPolicy
{
    /**
     * Lihat daftar user (global, lintas lab).
     * HANYA super_admin (di-bypass otomatis lewat Gate::before di AppServiceProvider).
     * lab_admin/staff lain tidak boleh lihat daftar user global sama sekali.
     */
    public function viewAny(User $authUser): bool
    {
        return false;
    }

    /**
     * Buat user baru. HANYA super_admin.
     */
    public function create(User $authUser): bool
    {
        return false;
    }

    /**
     * Update data user (nama, role, status aktif, dll). HANYA super_admin.
     * lab_admin TIDAK boleh mengubah status/role pengguna manapun.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        return false;
    }

    /**
     * Hapus user.
     * - super_admin: bebas (sudah di-bypass Gate::before).
     * - lab_admin: HANYA boleh hapus akun customer yang punya riwayat booking
     *   di lab yang dia kelola (dipakai untuk hapus akun pelanggar). Tidak boleh
     *   hapus sesama staff/admin, dan tidak boleh hapus customer di lab lain.
     * - role lain: tidak boleh sama sekali.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('lab_admin')) {
            if (!$targetUser->hasRole('customer')) {
                return false;
            }

            $labIds = $authUser->labs()->pluck('lab_id');

            return Booking::where('user_id', $targetUser->id)
                ->whereIn('lab_id', $labIds)
                ->exists();
        }

        return false;
    }
}