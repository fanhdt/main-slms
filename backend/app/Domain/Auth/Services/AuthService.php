<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService extends BaseService
{
    /**
     * Login user dan kembalikan token Sanctum.
     *
     * @throws ApiException
     */
    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new ApiException('Email atau password salah.', 401);
        }

        if (! $user->is_active) {
            throw new ApiException('Akun Anda tidak aktif. Hubungi administrator.', 403);
        }

        // Hapus token lama dari device yang sama
        $user->tokens()->where('name', $dto->device)->delete();

        $token = $user->createToken($dto->device)->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Register user baru dengan role Customer.
     *
     * @throws ApiException
     */
    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => $dto->password,
            'phone'    => $dto->phone ?: null,
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::Customer->value);

        $token = $user->createToken($dto->device)->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout — hapus token yang sedang dipakai.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}