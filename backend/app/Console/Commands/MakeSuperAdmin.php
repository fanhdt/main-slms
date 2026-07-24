<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\User\Enums\UserRole;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-super-admin {email : Email user yang akan dijadikan super admin}';

    protected $description = 'Jadikan seorang user sebagai Super Admin. HANYA dijalankan langsung di server oleh developer/administrator — tidak ada jalur lain (API/UI) untuk melakukan ini, demi mencegah privilege escalation.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User dengan email \"{$email}\" tidak ditemukan.");
            return self::FAILURE;
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            $this->info("User \"{$user->email}\" sudah menjadi Super Admin.");
            return self::SUCCESS;
        }

        $this->warn("Kamu akan menjadikan \"{$user->name}\" ({$user->email}) sebagai Super Admin.");
        $this->warn('Super Admin punya akses PENUH ke seluruh sistem dan semua laboratorium.');

        if (!$this->confirm('Yakin ingin melanjutkan?')) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        $user->syncRoles([UserRole::SuperAdmin->value]);

        $this->info("Berhasil! \"{$user->email}\" sekarang adalah Super Admin.");

        return self::SUCCESS;
    }
}