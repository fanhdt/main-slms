<?php

declare(strict_types=1);

namespace App\Domain\User\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Models\User;
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
     * Hanya super_admin (viewAny policy selalu false untuk role lain).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->paginate($request->all());

        return $this->success(
            UserResource::collection($users)->response()->getData(true)
        );
    }

    /**
     * Lihat detail satu user. Hanya super_admin.
     */
    public function show(string $uuid): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $user = $this->userService->findByUuid($uuid);

        return $this->success(new UserResource($user));
    }

    /**
     * Buat user baru. Hanya super_admin.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->create(
            CreateUserDTO::fromRequest($request->validated())
        );

        return $this->created(new UserResource($user), 'User berhasil dibuat.');
    }

    /**
     * Update user. Hanya super_admin (lab_admin tidak boleh ubah status/role siapapun).
     */
    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $targetUser = $this->userService->findByUuid($uuid);
        $this->authorize('update', $targetUser);

        $user = $this->userService->update(
            $uuid,
            UpdateUserDTO::fromRequest($request->validated())
        );

        return $this->success(new UserResource($user), 'User berhasil diupdate.');
    }

    /**
     * Hapus user.
     * super_admin: bebas.
     * lab_admin: hanya customer yang punya booking di labnya (pelanggar).
     */
    public function destroy(string $uuid): JsonResponse
    {
        $targetUser = $this->userService->findByUuid($uuid);
        $this->authorize('delete', $targetUser);

        $this->userService->delete($uuid);

        return $this->successMessage('User berhasil dihapus.');
    }

    /**
     * Assign role ke user. Hanya super_admin.
     */
    public function assignRole(Request $request, string $uuid): JsonResponse
    {
        $targetUser = $this->userService->findByUuid($uuid);
        $this->authorize('update', $targetUser);

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = $this->userService->assignRole($uuid, $request->role);

        return $this->success(new UserResource($user), 'Role berhasil diassign.');
    }

    public function labCustomers(Request $request, string $labId): JsonResponse
{
    $users = $this->userService->paginateLabCustomers((int) $labId, $request->all());

    return $this->success(
        UserResource::collection($users)->response()->getData(true)
    );
}
}