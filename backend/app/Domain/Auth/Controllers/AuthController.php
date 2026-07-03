<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Requests\RegisterRequest;
use App\Domain\Auth\Resources\AuthUserResource;
use App\Domain\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return $this->created([
            'user'  => new AuthUserResource($result['user']),
            'token' => $result['token'],
        ], 'Registrasi berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successMessage('Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new AuthUserResource($request->user())
        );
    }
}