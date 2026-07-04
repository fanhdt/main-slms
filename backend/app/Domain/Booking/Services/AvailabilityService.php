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

    /**
     * Status booking yang dianggap "menempati" slot waktu.
     * Canceled & Rejected tidak menempati apapun.
     */
    private const OCCUPYING_STATUSES = [
        BookingStatus::Pending,
        BookingStatus::Approved,
        BookingStatus::Ongoing,
        BookingStatus::Completed,
    ];

    /**
     * Jam buka/tutup operasional lab. Default 07:00–16:00,
     * bisa dioverride lewat Lab::settings['operational_hours'].
     *
     * @return array{open: string, close: string}
     */
    public function operationalHours(Lab $lab): array
    {
        $settings = $lab->settings['operational_hours'] ?? [];

        return [
            'open'  => $settings['open'] ?? self::DEFAULT_OPEN,
            'close' => $settings['close'] ?? self::DEFAULT_CLOSE,
        ];
    }

    /**
     * Ringkasan ketersediaan per hari dalam 1 bulan, untuk render kalender bulan.
     * Tiap hari diberi status: 'available' (belum ada booking), 'partial'
     * (ada booking tapi masih ada jam kosong), atau 'full' (semua jam operasional terisi).
     *
     * @return array{operational_hours: array, days: array<string, string>}
     */
    public function monthSummary(Lab $lab, string $month): array
    {
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
     * Detail slot per jam untuk 1 hari spesifik: mana yang occupied, mana yang available.
     *
     * @return array{
     *     operational_hours: array,
     *     occupied_ranges: array<int, array{start: string, end: string}>,
     *     slots: array<int, array{start: string, end: string, available: bool}>
     * }
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
            ->get(['start_time', 'end_time']);

        $occupiedRanges = $bookings->map(fn ($b) => [
            'start' => $b->start_time->format('H:i'),
            'end'   => $b->end_time->format('H:i'),
        ])->values()->all();

        // Generate slot per jam (07:00-08:00, 08:00-09:00, dst)
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
            'slots'             => $slots,
        ];
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return ($h * 60) + $m;
    }

    /**
     * Hitung berapa menit dari sebuah booking yang overlap dengan jam operasional.
     *
     * @param  array{open: string, close: string}  $hours
     */
    private function overlapMinutes(Carbon $start, Carbon $end, array $hours): int
    {
        $opStart = $start->copy()->setTimeFromTimeString($hours['open']);
        $opEnd = $start->copy()->setTimeFromTimeString($hours['close']);

        $overlapStart = $start->greaterThan($opStart) ? $start : $opStart;
        $overlapEnd = $end->lessThan($opEnd) ? $end : $opEnd;

        return max(0, (int) round($overlapStart->diffInMinutes($overlapEnd, false)));
    }
}