<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthService extends BaseService
{
    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new ApiException('Email atau password salah.', 401);
        }

        if (!$user->is_active) {
            throw new ApiException('Akun Anda tidak aktif. Hubungi administrator.', 403);
        }

        // NEW — blocking: tidak bisa login sebelum verifikasi email
        if (!$user->hasVerifiedEmail()) {
            throw new ApiException('Email belum diverifikasi. Cek inbox kamu atau minta kirim ulang.', 403, [
                'requires_verification' => true,
                'email' => $user->email,
            ]);
        }

        $user->tokens()->where('name', $dto->device)->delete();
        $token = $user->createToken($dto->device)->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => $dto->password,
            'phone'     => $dto->phone ?: null,
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::Customer->value);
        $user->sendEmailVerificationNotification();

        // NEW — tidak langsung kasih token, user harus verifikasi dulu
        return ['user' => $user];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function sendResetLink(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return;
        }

        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $token, string $email, string $password): void
    {
        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException($this->resetErrorMessage($status), 422);
        }
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new ApiException('Password saat ini salah.', 422);
        }

        $user->forceFill(['password' => Hash::make($newPassword)])->save();
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
    }

    // NEW
    public function resendVerification(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }

    private function resetErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'Link reset password tidak valid atau sudah kadaluarsa.',
            Password::INVALID_USER  => 'Email tidak ditemukan.',
            default                 => 'Gagal mereset password. Coba lagi.',
        };
    }

    public function updateAvatar(User $user, \Illuminate\Http\UploadedFile $file): User
    {
        if ($user->avatar) {
            Storage::disk('s3')->delete($user->avatar);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "avatars/{$user->uuid}/{$filename}";

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        $user->update(['avatar' => $path]);

        return $user->fresh();
    }

     public function deleteOwnAccount(User $user, string $password): void
    {
        if (!Hash::check($password, $user->password)) {
            throw new ApiException('Password salah.', 422);
        }

        $user->tokens()->delete(); // logout semua device
        $user->delete(); // soft delete, data tetap ada di DB (deleted_at terisi)
    }
}