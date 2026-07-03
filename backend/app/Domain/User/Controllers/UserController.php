<?php

declare(strict_types=1);

namespace App\Domain\User\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Requests\CreateUserRequest;
use App\Domain\User\Requests\UpdateUserRequest;
use App\Domain\User\Resources\UserResource;
use App\Domain\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * List semua user dengan filter dan pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Domain\User\Models\User::class);

        $users = $this->userService->paginate($request->all());

        return $this->success(
            UserResource::collection($users)->response()->getData(true)
        );
    }

    /**
     * Lihat detail satu user.
     */
    public function show(string $uuid): JsonResponse
    {
        $this->authorize('viewAny', \App\Domain\User\Models\User::class);

        $user = $this->userService->findByUuid($uuid);

        return $this->success(new UserResource($user));
    }

    /**
     * Buat user baru.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Domain\User\Models\User::class);

        $user = $this->userService->create(
            CreateUserDTO::fromRequest($request->validated())
        );

        return $this->created(new UserResource($user), 'User berhasil dibuat.');
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $this->authorize('update', \App\Domain\User\Models\User::class);

        $user = $this->userService->update(
            $uuid,
            UpdateUserDTO::fromRequest($request->validated())
        );

        return $this->success(new UserResource($user), 'User berhasil diupdate.');
    }

    /**
     * Hapus user.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->authorize('delete', \App\Domain\User\Models\User::class);

        $this->userService->delete($uuid);

        return $this->successMessage('User berhasil dihapus.');
    }

    /**
     * Assign role ke user.
     */
    public function assignRole(Request $request, string $uuid): JsonResponse
    {
        $this->authorize('update', \App\Domain\User\Models\User::class);

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = $this->userService->assignRole($uuid, $request->role);

        return $this->success(new UserResource($user), 'Role berhasil diassign.');
    }
}