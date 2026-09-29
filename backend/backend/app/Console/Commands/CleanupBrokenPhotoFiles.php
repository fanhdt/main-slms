<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Photo\Models\PhotoFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupBrokenPhotoFiles extends Command
{
    protected $signature = 'photo:cleanup-broken {--dry-run : Cuma tampilkan tanpa menghapus}';
    protected $description = 'Hapus record photo_files yang file fisiknya tidak ada di storage (data testing lama)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $broken = collect();

        PhotoFile::chunkById(50, function ($files) use (&$broken) {
            foreach ($files as $file) {
                if (!Storage::disk($file->disk)->exists($file->path)) {
                    $broken->push($file);
                }
            }
        });

        if ($broken->isEmpty()) {
            $this->info('Tidak ada file yang broken.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Project ID', 'Filename', 'Path'],
            $broken->map(fn ($f) => [$f->id, $f->project_id, $f->filename, $f->path])
        );

        if ($dryRun) {
            $this->warn("Ditemukan {$broken->count()} file broken. Jalankan tanpa --dry-run untuk hapus.");
            return self::SUCCESS;
        }

        if ($this->confirm("Hapus {$broken->count()} record di atas?")) {
            PhotoFile::whereIn('id', $broken->pluck('id'))->delete();
            $this->info('Berhasil dihapus.');
        }

        return self::SUCCESS;
    }
}