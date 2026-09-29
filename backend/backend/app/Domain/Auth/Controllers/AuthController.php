<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\Requests\ChangePasswordRequest;
use App\Domain\Auth\Requests\ForgotPasswordRequest;
use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Requests\RegisterRequest;
use App\Domain\Auth\Requests\ResetPasswordRequest;
use App\Domain\Auth\Requests\UpdateProfileRequest;
use App\Domain\Auth\Requests\ResendVerificationRequest;
use App\Domain\Auth\Requests\DeleteAccountRequest;
use App\Domain\Auth\Requests\UpdateAvatarRequest;
use App\Domain\Auth\Resources\AuthUserResource;
use App\Domain\Auth\Services\AuthService;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            LoginDTO::fromRequest($request->validated())
        );

        return $this->success([
            'user'  => new AuthUserResource($result['user']),
            'token' => $result['token'],
        ], 'Login berhasil.');
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            RegisterDTO::fromRequest($request->validated())
        );

        // NEW — tidak ada token, karena belum diverifikasi
        return $this->created([
            'user' => new AuthUserResource($result['user']),
        ], 'Registrasi berhasil. Cek email kamu untuk verifikasi sebelum login.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successMessage('Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new AuthUserResource($request->user()));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendResetLink($request->validated('email'));

        return $this->successMessage('Kalau email terdaftar, link reset password sudah dikirim.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->validated('token'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return $this->successMessage('Password berhasil direset. Silakan login dengan password baru.');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success(new AuthUserResource($user), 'Profil berhasil diupdate.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return $this->successMessage('Password berhasil diubah.');
    }

    // NEW — diklik dari email, signature sudah divalidasi middleware 'signed'
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return Redirect::away(
                env('FRONTEND_URL', 'http://localhost:5173') . '/email-verified?status=invalid'
            );
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return Redirect::away(
            env('FRONTEND_URL', 'http://localhost:5173') . '/email-verified?status=success'
        );
    }

    // NEW
     public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $this->authService->resendVerification($request->validated('email'));

        return $this->successMessage('Kalau email terdaftar dan belum diverifikasi, link verifikasi sudah dikirim ulang.');
    }
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $this->authService->updateAvatar($request->user(), $request->file('avatar'));

        return $this->success(new AuthUserResource($user), 'Avatar berhasil diupdate.');
    }

    /**
     * DELETE /auth/me
     */
    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $this->authService->deleteOwnAccount($request->user(), $request->validated('password'));

        return $this->successMessage('Akun berhasil dihapus.');
    }
}