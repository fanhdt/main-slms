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

        $this->command->info('Labs seeded successfully.');
    }
}
