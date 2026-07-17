<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Core\Services\BaseService;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\BookingType;
use App\Domain\Booking\Models\Booking;
use App\Domain\Lab\Models\Lab;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityService extends BaseService
{
    private const DEFAULT_OPEN = '07:00';
    private const DEFAULT_CLOSE = '16:00';

    private const OCCUPYING_STATUSES = [
        BookingStatus::Pending,
        BookingStatus::Approved,
        BookingStatus::Ongoing,
        BookingStatus::Completed,
    ];

    public function operationalHours(Lab $lab): array
    {
        $settings = $lab->settings['operational_hours'] ?? [];

        return [
            'open'  => $settings['open'] ?? self::DEFAULT_OPEN,
            'close' => $settings['close'] ?? self::DEFAULT_CLOSE,
        ];
    }

    public function monthSummary(Lab $lab, string $month): array
    {
        // ... TIDAK BERUBAH, biarkan seperti sebelumnya ...
        $hours = $this->operationalHours($lab);
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $totalMinutesPerDay = $this->timeToMinutes($hours['close']) - $this->timeToMinutes($hours['open']);

        $bookings = Booking::where('lab_id', $lab->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, self::OCCUPYING_STATUSES))
            ->whereIn('booking_type', [BookingType::LabRental->value, BookingType::Service->value])
            ->whereBetween('start_time', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get(['start_time', 'end_time']);

        $occupiedMinutesByDay = [];
        foreach ($bookings as $booking) {
            $day = $booking->start_time->format('Y-m-d');
            $minutes = $this->overlapMinutes($booking->start_time, $booking->end_time, $hours);
            $occupiedMinutesByDay[$day] = ($occupiedMinutesByDay[$day] ?? 0) + $minutes;
        }

        $days = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $occupied = $occupiedMinutesByDay[$key] ?? 0;

            $days[$key] = match (true) {
                $occupied <= 0 => 'available',
                $occupied >= $totalMinutesPerDay => 'full',
                default => 'partial',
            };
        }

        return [
            'operational_hours' => $hours,
            'days'              => $days,
        ];
    }

    /**
     * Detail slot per jam untuk 1 hari, DITAMBAH ringkasan aktivitas (jam + label generik)
     * untuk ditampilkan publik di landing page — TANPA data pribadi customer.
     */
    public function dayDetail(Lab $lab, string $date): array
    {
        $hours = $this->operationalHours($lab);
        $dayStart = Carbon::parse($date)->setTimeFromTimeString($hours['open']);
        $dayEnd = Carbon::parse($date)->setTimeFromTimeString($hours['close']);

        $bookings = Booking::where('lab_id', $lab->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, self::OCCUPYING_STATUSES))
            ->whereIn('booking_type', [BookingType::LabRental->value, BookingType::Service->value])
            ->whereDate('start_time', $date)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time', 'booking_type', 'purpose']);

        $occupiedRanges = $bookings->map(fn ($b) => [
            'start' => $b->start_time->format('H:i'),
            'end'   => $b->end_time->format('H:i'),
        ])->values()->all();

        // NEW — ringkasan aktivitas untuk ditampilkan publik (generik, tanpa nama/catatan customer)
        $activities = $bookings->map(fn ($b) => [
            'start' => $b->start_time->format('H:i'),
            'end'   => $b->end_time->format('H:i'),
            'label' => $this->activityLabel($b->booking_type, $b->purpose),
        ])->values()->all();

        $slots = [];
        $cursor = $dayStart->copy();
        while ($cursor->lt($dayEnd)) {
            $slotEnd = $cursor->copy()->addHour();

            $isOccupied = $bookings->contains(function ($booking) use ($cursor, $slotEnd) {
                return $cursor->lt($booking->end_time) && $slotEnd->gt($booking->start_time);
            });

            $slots[] = [
                'start'     => $cursor->format('H:i'),
                'end'       => $slotEnd->format('H:i'),
                'available' => !$isOccupied,
            ];

            $cursor = $slotEnd;
        }

        return [
            'operational_hours' => $hours,
            'occupied_ranges'   => $occupiedRanges,
            'activities'        => $activities, // NEW
            'slots'             => $slots,
        ];
    }

    /**
     * Label generik untuk aktivitas — sengaja tidak menyertakan nama/catatan
     * customer supaya aman ditampilkan di landing page publik.
     */
    private function activityLabel(BookingType $type, ?\App\Domain\Booking\Enums\BookingPurpose $purpose): string
    {
        return match ($type) {
            BookingType::LabRental => match ($purpose) {
                \App\Domain\Booking\Enums\BookingPurpose::Academic     => 'Kegiatan Akademik / Perkuliahan',
                \App\Domain\Booking\Enums\BookingPurpose::Organization => 'Kegiatan Organisasi Mahasiswa',
                \App\Domain\Booking\Enums\BookingPurpose::Public       => 'Kegiatan Umum',
                default => 'Peminjaman Lab',
            },
            BookingType::Service => 'Sesi Jasa / Paket',
            BookingType::AssetRental => 'Sewa Peralatan',
        };
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return ($h * 60) + $m;
    }

    private function overlapMinutes(Carbon $start, Carbon $end, array $hours): int
    {
        $opStart = $start->copy()->setTimeFromTimeString($hours['open']);
        $opEnd = $start->copy()->setTimeFromTimeString($hours['close']);

        $overlapStart = $start->greaterThan($opStart) ? $start : $opStart;
        $overlapEnd = $end->lessThan($opEnd) ? $end : $opEnd;

        return max(0, (int) round($overlapStart->diffInMinutes($overlapEnd, false)));
    }
}