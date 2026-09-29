<?php

declare(strict_types=1);

namespace App\Domain\Booking\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Lab\Services\LabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends ApiController
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly LabService $labService,
    ) {}

    /**
     * GET /labs/{slug}/availability?month=2026-07
     * Ringkasan status per hari dalam 1 bulan (available/partial/full).
     */
    public function month(Request $request, string $slug): JsonResponse
{
    $request->validate([
        'month' => ['required', 'date_format:Y-m'],
    ]);

    $lab = $this->labService->findBySlug($slug);
    $summary = $this->availabilityService->monthSummary($lab, $request->input('month'));

    return $this->success($summary);
}
    /**
     * GET /labs/{slug}/availability/{date}
     * Detail slot per jam untuk 1 hari (07:00-16:00 default).
     */
    public function day(string $slug, string $date): JsonResponse
    {
        $lab = $this->labService->findBySlug($slug);
        $detail = $this->availabilityService->dayDetail($lab, $date);

        return $this->success($detail);
    }
}