<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@slms.local'],
            [
                'name'      => 'Super Admin',
                'password'  => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $admin->assignRole(UserRole::SuperAdmin->value);

        $this->command->info("Super Admin seeded: admin@slms.local / password");
        $this->command->warn("⚠ Ganti password sebelum production!");
    }
}
