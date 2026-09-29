<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return false;
    }

    public function create(User $authUser): bool
    {
        return false;
    }

    /**
     * Update data user (nama, role, status aktif, dll). HANYA super_admin.
     * Super Admin lain TIDAK BOLEH diubah lewat aplikasi sama sekali —
     * termasuk oleh sesama super_admin — untuk mencegah satu akun super_admin
     * yang disusupi bisa mengubah/mendemote super_admin lain diam-diam.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if ($targetUser->hasRole(UserRole::SuperAdmin->value)) {
            return false;
        }

        return false; // di-bypass Gate::before untuk super_admin yang login
    }

    /**
     * Hapus user.
     * - super_admin: bebas, KECUALI menghapus sesama super_admin (dicegah di bawah).
     * - lab_admin: HANYA boleh hapus akun customer yang punya riwayat booking
     *   di lab yang dia kelola.
     * - role lain: tidak boleh sama sekali.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        // Super Admin tidak boleh dihapus lewat aplikasi oleh siapapun,
        // termasuk oleh dirinya sendiri atau super_admin lain.
        if ($targetUser->hasRole(UserRole::SuperAdmin->value)) {
            return false;
        }

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