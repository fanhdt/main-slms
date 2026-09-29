<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Lab\Models\Lab;
use App\Domain\Report\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailyReport extends Command
{
    protected $signature = 'reports:daily-recap {--date= : Tanggal yang direkap (default kemarin)}';
    protected $description = 'Buat rekap harian booking per lab (Excel + PDF) dan kirim ke lab admin';

    public function handle(ReportService $reportService): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now()->subDay();
        $labs = Lab::where('is_active', true)->get();

        foreach ($labs as $lab) {
            $reportService->generateDaily($lab, $date);
            $this->line("✓ {$lab->name}");
        }

        $this->info("Rekap harian untuk {$labs->count()} lab berhasil dibuat ({$date->toDateString()}).");
        return self::SUCCESS;
    }
}