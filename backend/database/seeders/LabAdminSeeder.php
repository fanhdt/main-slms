<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Lab\Models\Lab;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class LabAdminSeeder extends Seeder
{
    /**
     * Daftar lab admin yang dibuat, dikaitkan by slug lab.
     * Password sama untuk semua — WAJIB diganti sebelum production.
     */
    private const ADMINS = [
        [
            'lab_slug' => 'photography',
            'email'    => 'admin.fotografi@slms.local',
            'name'     => 'Admin Lab Fotografi',
        ],
        [
            'lab_slug' => 'komputer',
            'email'    => 'admin.komputer@slms.local',
            'name'     => 'Admin Lab Komputer',
        ],
        [
            'lab_slug' => 'audio',
            'email'    => 'admin.audio@slms.local',
            'name'     => 'Admin Lab Audio',
        ],
    ];

    public function run(): void
    {
        foreach (self::ADMINS as $data) {
            $lab = Lab::where('slug', $data['lab_slug'])->first();

            if (! $lab) {
                $this->command->warn("Lab dengan slug \"{$data['lab_slug']}\" tidak ditemukan, lewati {$data['email']}.");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => bcrypt('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([UserRole::LabAdmin->value]);

            // Kaitkan lab admin ini HANYA ke lab miliknya (role lab_admin = single-lab).
            $user->labs()->syncWithoutDetaching([
                $lab->id => ['role' => UserRole::LabAdmin->value],
            ]);

            $this->command->info("Lab Admin seeded: {$data['email']} / password -> {$lab->name}");
        }

        $this->command->warn('⚠ Ganti password semua lab admin sebelum production!');
    }
}