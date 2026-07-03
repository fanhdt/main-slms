<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Lab\Models\Lab;
use App\Domain\User\Models\User;

class UserLabService extends BaseService
{
    /**
     * Assign user ke lab.
     *
     * @throws ApiException
     */
    public function assign(string $userUuid, string $labUuid, ?string $role = null): User
    {
        $user = User::where('uuid', $userUuid)->first();
        if (! $user) {
            throw ApiException::notFound('User');
        }

        $lab = Lab::where('uuid', $labUuid)->first();
        if (! $lab) {
            throw ApiException::notFound('Lab');
        }

        // Kalau sudah assign, update role-nya
        $user->labs()->syncWithoutDetaching([
            $lab->id => ['role' => $role],
        ]);

        return $user->load('labs');
    }

    /**
     * Cabut akses user dari lab.
     *
     * @throws ApiException
     */
    public function revoke(string $userUuid, string $labUuid): void
    {
        $user = User::where('uuid', $userUuid)->first();
        if (! $user) {
            throw ApiException::notFound('User');
        }

        $lab = Lab::where('uuid', $labUuid)->first();
        if (! $lab) {
            throw ApiException::notFound('Lab');
        }

        $user->labs()->detach($lab->id);
    }

    /**
     * Ambil semua lab yang user ini punya akses.
     *
     * @throws ApiException
     */
    public function getUserLabs(string $userUuid): User
    {
        $user = User::with('labs')->where('uuid', $userUuid)->first();

        if (! $user) {
            throw ApiException::notFound('User');
        }

        return $user;
    }

    /**
     * Ambil semua user yang punya akses ke lab tertentu.
     *
     * @throws ApiException
     */
    public function getLabUsers(string $labUuid): Lab
    {
        $lab = Lab::with('users')->where('uuid', $labUuid)->first();

        if (! $lab) {
            throw ApiException::notFound('Lab');
        }

        return $lab;
    }
}