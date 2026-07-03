<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ---- Definisi semua permission ----
        $permissions = [
            // Lab management
            'labs.view',
            'labs.create',
            'labs.update',
            'labs.delete',
            'labs.branding',

            // User management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign-role',

            // Booking management
            'bookings.view',
            'bookings.view-own',
            'bookings.create',
            'bookings.update',
            'bookings.delete',
            'bookings.approve',
            'bookings.cancel',
            'bookings.checkin',

            // Asset management
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.delete',

            // Service management
            'services.view',
            'services.create',
            'services.update',
            'services.delete',

            // Package management
            'packages.view',
            'packages.create',
            'packages.update',
            'packages.delete',

            // Media management
            'media.view',
            'media.upload',
            'media.delete',
            'media.approve',

            // Report
            'reports.view',
            'reports.export',

            // Settings
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ---- Buat roles dan assign permissions ----

        // Super Admin — akses penuh, tidak perlu di-list permission satu per satu
        $superAdmin = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Lab Admin — kelola lab sendiri
        $labAdmin = Role::firstOrCreate(['name' => UserRole::LabAdmin->value, 'guard_name' => 'web']);
        $labAdmin->givePermissionTo([
            'labs.view', 'labs.update', 'labs.branding',
            'users.view', 'users.create', 'users.update', 'users.assign-role',
            'bookings.view', 'bookings.update', 'bookings.approve', 'bookings.cancel', 'bookings.checkin',
            'assets.view', 'assets.create', 'assets.update', 'assets.delete',
            'services.view', 'services.create', 'services.update', 'services.delete',
            'packages.view', 'packages.create', 'packages.update', 'packages.delete',
            'media.view', 'media.upload', 'media.delete', 'media.approve',
            'reports.view', 'reports.export',
            'settings.view', 'settings.update',
        ]);

        // Operator — operasional harian
        $operator = Role::firstOrCreate(['name' => UserRole::Operator->value, 'guard_name' => 'web']);
        $operator->givePermissionTo([
            'bookings.view', 'bookings.create', 'bookings.update', 'bookings.checkin',
            'assets.view',
            'services.view',
            'packages.view',
            'media.view', 'media.upload',
            'reports.view',
        ]);

        // Photographer
        $photographer = Role::firstOrCreate(['name' => UserRole::Photographer->value, 'guard_name' => 'web']);
        $photographer->givePermissionTo([
            'bookings.view',
            'assets.view',
            'media.view', 'media.upload',
        ]);

        // Editor
        $editor = Role::firstOrCreate(['name' => UserRole::Editor->value, 'guard_name' => 'web']);
        $editor->givePermissionTo([
            'bookings.view',
            'media.view', 'media.upload', 'media.approve',
        ]);

        // Customer — hanya lihat dan booking sendiri
        $customer = Role::firstOrCreate(['name' => UserRole::Customer->value, 'guard_name' => 'web']);
        $customer->givePermissionTo([
            'bookings.view-own', 'bookings.create',
            'services.view',
            'packages.view',
            'media.view',
        ]);

        // Guest — read only public
        $guest = Role::firstOrCreate(['name' => UserRole::Guest->value, 'guard_name' => 'web']);
        $guest->givePermissionTo([
            'services.view',
            'packages.view',
        ]);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
