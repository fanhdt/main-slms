<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Photo\Enums\PhotoProjectStatus;
use App\Domain\Photo\Models\PhotoProject;
use App\Domain\Photo\Services\PhotoDeliveryService;
use Illuminate\Console\Command;

class ExpirePhotoProjects extends Command
{
    protected $signature = 'photo:expire';
    protected $description = 'Tandai photo project "delivered" yang sudah lewat expires_at menjadi "expired"';

    public function handle(PhotoDeliveryService $photoService): int
    {
        $projects = PhotoProject::where('status', PhotoProjectStatus::Delivered)
            ->where('expires_at', '<=', now())
            ->get();

        if ($projects->isEmpty()) {
            $this->info('Tidak ada photo project yang perlu di-expire.');
            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $photoService->checkExpiry($project);
        }

        $this->info("{$projects->count()} photo project ditandai expired.");
        return self::SUCCESS;
    }
}