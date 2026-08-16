<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends ApiController
{
    /**
     * GET /auth/google/redirect — frontend arahkan browser ke sini.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * GET /auth/google/callback — Google redirect balik ke sini,
     * lalu kita redirect ke frontend dengan token di query string.
     */
    public function callback(): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return Redirect::away($frontendUrl . '/login?error=google_failed');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Kalau user sudah ada (daftar manual sebelumnya) tapi belum ada google_id, kaitkan.
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name'              => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Google User',
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'password'          => bcrypt(str()->random(32)), // password acak, tidak dipakai
                'is_active'         => true,
                'email_verified_at' => now(), // email dari Google sudah terverifikasi
            ]);
            $user->assignRole(UserRole::Customer->value);
        }

        if (!$user->is_active) {
            return Redirect::away($frontendUrl . '/login?error=inactive');
        }

        $token = $user->createToken('web')->plainTextToken;

        return Redirect::away($frontendUrl . '/auth/google/callback?token=' . $token);
    }
}