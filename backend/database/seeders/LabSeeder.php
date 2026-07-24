<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Lab\Models\Lab;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        Lab::firstOrCreate(
            ['slug' => 'photography'],
            [
                'name'            => 'Laboratorium Fotografi',
                'description'     => 'Laboratorium fotografi lengkap dengan studio, peralatan kamera profesional, dan layanan editing foto.',
                'primary_color'   => '#1a1a2e',
                'secondary_color' => '#e94560',
                'is_active'       => true,
                'is_photography_lab' => true,
                'contact'         => [
                    'email'   => 'foto@slms.local',
                    'phone'   => '08123456789',
                    'address' => 'Gedung A, Lantai 2',
                ],
                'settings' => [
                    'booking_advance_days' => 30,
                    'max_booking_per_day'  => 10,
                    'photo_expiry_days'    => 14,
                    'timezone'             => 'Asia/Jakarta',
                ],
            ]
        );

        Lab::firstOrCreate(
            ['slug' => 'komputer'],
            [
                'name'            => 'Laboratorium Komputer',
                'description'     => 'Laboratorium komputer dengan unit PC/laptop untuk praktikum, pelatihan, dan pengerjaan tugas mata kuliah.',
                'primary_color'   => '#0f172a',
                'secondary_color' => '#2563eb',
                'is_active'       => true,
                'is_photography_lab' => false,
                'contact'         => [
                    'email'   => 'komputer@slms.local',
                    'phone'   => '08123456790',
                    'address' => 'Gedung B, Lantai 1',
                ],
                'settings' => [
                    'booking_advance_days' => 30,
                    'max_booking_per_day'  => 10,
                    'timezone'             => 'Asia/Jakarta',
                ],
            ]
        );

        Lab::firstOrCreate(
            ['slug' => 'audio'],
            [
                'name'            => 'Laboratorium Audio',
                'description'     => 'Laboratorium audio dengan ruang rekaman, mixer, dan peralatan sound production.',
                'primary_color'   => '#1c1917',
                'secondary_color' => '#f59e0b',
                'is_active'       => true,
                'is_photography_lab' => false,
                'contact'         => [
                    'email'   => 'audio@slms.local',
                    'phone'   => '08123456791',
                    'address' => 'Gedung B, Lantai 2',
                ],
                'settings' => [
                    'booking_advance_days' => 30,
                    'max_booking_per_day'  => 10,
                    'timezone'             => 'Asia/Jakarta',
                ],
            ]
        );

        $this->command->info('Labs seeded successfully.');
    }
}