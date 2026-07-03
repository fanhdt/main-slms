<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService extends BaseService
{
    /**
     * Ambil semua user dengan pagination dan filter.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        // Filter by role
        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }

        // Filter by status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Search by nama atau email
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'ilike', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'ilike', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Ambil satu user berdasarkan UUID.
     *
     * @throws ApiException
     */
    public function findByUuid(string $uuid): User
    {
        $user = User::with('roles')->where('uuid', $uuid)->first();

        if (! $user) {
            throw ApiException::notFound('User');
        }

        return $user;
    }

    /**
     * Buat user baru dengan role yang ditentukan admin.
     */
    public function create(CreateUserDTO $dto): User
    {
        $user = User::create([
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => $dto->password,
            'phone'     => $dto->phone,
            'is_active' => $dto->isActive,
        ]);

        $user->assignRole($dto->role);

        return $user->load('roles');
    }

    /**
     * Update user berdasarkan UUID.
     *
     * @throws ApiException
     */
    public function update(string $uuid, UpdateUserDTO $dto): User
    {
        $user = $this->findByUuid($uuid);

        $data = $dto->toArray();

        if (! empty($data)) {
            $user->update($data);
        }

        // Update role jika dikirim
        if ($dto->role !== null) {
            $user->syncRoles([$dto->role]);
        }

        return $user->fresh('roles');
    }

    /**
     * Hapus user (soft delete).
     *
     * @throws ApiException
     */
    public function delete(string $uuid): void
    {
        $user = $this->findByUuid($uuid);
        $user->delete();
    }

    /**
     * Assign role ke user.
     *
     * @throws ApiException
     */
    public function assignRole(string $uuid, string $role): User
    {
        $user = $this->findByUuid($uuid);
        $user->syncRoles([$role]);

        return $user->fresh('roles');
    }
}