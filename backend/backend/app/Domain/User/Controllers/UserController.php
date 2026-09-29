<?php

declare(strict_types=1);

namespace App\Domain\User\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Enums\UserRole;
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
     * Role super_admin sudah ditolak lewat validasi di CreateUserRequest
     * (lihat UserRole::assignableRoles()).
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
     * Role super_admin sudah ditolak lewat validasi di UpdateUserRequest.
     */
    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $targetUser = $this->userService->findByUuid($uuid);
        $this->authorize('update', $targetUser);

        // Jaring pengaman tambahan: kalau target user sudah super_admin,
        // role-nya tidak boleh diubah lewat endpoint ini sama sekali.
        if ($targetUser->hasRole(UserRole::SuperAdmin->value) && $request->has('role')) {
            return $this->forbidden('Role Super Admin tidak bisa diubah lewat aplikasi.');
        }

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
     *
     * PENTING: role super_admin TIDAK BOLEH diberikan lewat endpoint ini,
     * dalam kondisi apapun. Kalau ada kebutuhan menaikkan seseorang jadi
     * super_admin, itu harus dilakukan langsung lewat CLI di server oleh
     * developer (php artisan user:make-super-admin {email}), bukan lewat
     * aplikasi — supaya tidak ada jalur privilege escalation dari sisi UI/API.
     */
    public function assignRole(Request $request, string $uuid): JsonResponse
    {
        $targetUser = $this->userService->findByUuid($uuid);
        $this->authorize('update', $targetUser);

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        if ($request->input('role') === UserRole::SuperAdmin->value) {
            return $this->forbidden(
                'Role Super Admin tidak bisa diberikan lewat aplikasi. Hubungi developer untuk melakukannya langsung di server.'
            );
        }

        // Jaring pengaman tambahan: kalau target user sudah super_admin,
        // jangan biarkan role-nya diturunkan/diubah lewat endpoint ini.
        if ($targetUser->hasRole(UserRole::SuperAdmin->value)) {
            return $this->forbidden('Role Super Admin tidak bisa diubah lewat aplikasi.');
        }

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