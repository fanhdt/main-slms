<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Photo\Enums\PhotoProjectStatus;
use App\Domain\Photo\Models\PhotoProject;
use App\Domain\Photo\Services\PhotoDeliveryService;
use Illuminate\Console\Command;

class CleanupExpiredPhotoFiles extends Command
{
    protected $signature = 'photo:cleanup-expired-files {--grace-days=7 : Berapa hari setelah status expired baru file dihapus}';
    protected $description = 'Hapus file fisik (preview/edited/final) dari storage untuk photo project yang sudah expired melewati grace period';

    public function handle(PhotoDeliveryService $photoService): int
    {
        $graceDays = (int) $this->option('grace-days');

        $projects = PhotoProject::where('status', PhotoProjectStatus::Expired)
            ->whereNull('files_purged_at')
            ->where('updated_at', '<=', now()->subDays($graceDays))
            ->with('files')
            ->get();

        if ($projects->isEmpty()) {
            $this->info('Tidak ada photo project yang perlu dibersihkan filenya.');

            return self::SUCCESS;
        }

        $totalFiles = 0;
        foreach ($projects as $project) {
            $deleted = $photoService->purgeExpiredFiles($project);
            $totalFiles += $deleted;
            $this->line("Project {$project->uuid}: {$deleted} file dihapus.");
        }

        $this->info("Selesai. {$projects->count()} project diproses, {$totalFiles} file dihapus dari storage.");

        return self::SUCCESS;
    }
}