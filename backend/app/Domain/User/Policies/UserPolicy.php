<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Domain\User\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('users.view');
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('users.create');
    }

    public function update(User $authUser): bool
    {
        return $authUser->hasPermissionTo('users.update');
    }

    public function delete(User $authUser): bool
    {
        return $authUser->hasPermissionTo('users.delete');
    }
}