<?php

declare(strict_types=1);

namespace App\Domain\Report\Services;

use App\Core\Services\BaseService;
use App\Domain\Booking\Models\Booking;
use App\Domain\Lab\Models\Lab;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Report\Enums\ReportType;
use App\Domain\Report\Exports\BookingRecapExport;
use App\Domain\Report\Mail\ReportGeneratedMail;
use App\Domain\Report\Models\Report;
use App\Domain\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportService extends BaseService
{
    private const DISK = 's3';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function generateDaily(Lab $lab, Carbon $date): Report
    {
        return $this->generate($lab, ReportType::Daily, $date->copy()->startOfDay(), $date->copy()->endOfDay());
    }

    public function generateWeekly(Lab $lab, Carbon $referenceDate): Report
    {
        $start = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
        $end   = $referenceDate->copy()->endOfWeek(Carbon::SUNDAY);

        return $this->generate($lab, ReportType::Weekly, $start, $end);
    }

    private function generate(Lab $lab, ReportType $type, Carbon $start, Carbon $end): Report
    {
        $bookings = Booking::where('lab_id', $lab->id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['user', 'items.service', 'items.package'])
            ->orderBy('created_at')
            ->get();

        $totalRevenue = (float) $bookings->where('payment_status', 'paid')->sum('total_price');

        $summary = [
            'by_status'  => $bookings->groupBy(fn (Booking $b) => $b->status->value)->map->count(),
            'by_type'    => $bookings->groupBy(fn (Booking $b) => $b->booking_type->value)->map->count(),
            'by_payment' => $bookings->groupBy(fn (Booking $b) => $b->payment_status->value)->map->count(),
        ];

        $rows = $bookings->map(fn (Booking $b) => [
            $b->booking_code,
            $b->user?->name ?? '-',
            $b->user?->nim ?? '-',
            $b->booking_type->label(),
            $b->status->label(),
            $b->payment_status->label(),
            (float) $b->total_price,
            $b->created_at->format('d-m-Y H:i'),
        ])->toArray();

        $folder = "reports/{$lab->id}/{$type->value}";
        $baseFilename = Str::slug($lab->slug) . '-' . $type->value . '-' . $start->format('Ymd') . '-' . Str::random(6);

        $excelPath = "{$folder}/{$baseFilename}.xlsx";
        Excel::store(new BookingRecapExport($rows, "Rekap {$type->label()} {$lab->name}"), $excelPath, self::DISK);

        $pdfPath = "{$folder}/{$baseFilename}.pdf";
        $pdfBinary = Pdf::loadView('reports.recap', compact('lab', 'type', 'start', 'end', 'bookings', 'summary', 'totalRevenue'))->output();
        Storage::disk(self::DISK)->put($pdfPath, $pdfBinary);

        $report = Report::create([
            'lab_id'         => $lab->id,
            'type'           => $type->value,
            'period_start'   => $start->toDateString(),
            'period_end'     => $end->toDateString(),
            'total_bookings' => $bookings->count(),
            'total_revenue'  => $totalRevenue,
            'summary'        => $summary,
            'excel_path'     => $excelPath,
            'pdf_path'       => $pdfPath,
            'disk'           => self::DISK,
        ]);

        $this->notifyAndMail($lab, $report);

        return $report;
    }

    private function notifyAndMail(Lab $lab, Report $report): void
    {
        $periodLabel = $report->type === ReportType::Daily
            ? $report->period_start->translatedFormat('d M Y')
            : $report->period_start->translatedFormat('d M') . ' – ' . $report->period_end->translatedFormat('d M Y');

        $this->notificationService->notifyLabStaff(
            labId: $lab->id,
            type: $report->type === ReportType::Daily ? 'DailyReportGenerated' : 'WeeklyReportGenerated',
            title: "Rekap {$report->type->label()} — {$periodLabel}",
            body: "{$report->total_bookings} booking, total pendapatan Rp" . number_format((float) $report->total_revenue, 0, ',', '.') . '.',
            data: ['report_uuid' => $report->uuid, 'lab_id' => $lab->id],
        );

        $recipients = User::role('lab_admin')
            ->whereHas('labs', fn ($q) => $q->where('lab_id', $lab->id))
            ->get();

        foreach ($recipients as $admin) {
            Mail::to($admin->email)->queue(new ReportGeneratedMail($report, $lab, $admin));
        }
    }
}