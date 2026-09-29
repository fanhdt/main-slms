<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Lab\Models\Lab;
use App\Domain\Report\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyReport extends Command
{
    protected $signature = 'reports:weekly-recap {--date= : Tanggal referensi minggu (default hari ini)}';
    protected $description = 'Buat rekap mingguan booking per lab (Excel + PDF) dan kirim ke lab admin';

    public function handle(ReportService $reportService): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $labs = Lab::where('is_active', true)->get();

        foreach ($labs as $lab) {
            $reportService->generateWeekly($lab, $date);
            $this->line("✓ {$lab->name}");
        }

        $this->info("Rekap mingguan untuk {$labs->count()} lab berhasil dibuat.");
        return self::SUCCESS;
    }
}