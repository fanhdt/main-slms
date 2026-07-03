<?php

declare(strict_types=1);

namespace App\Domain\Photo\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Photo\Resources\PhotoProjectResource;
use App\Domain\Photo\Services\PhotoDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoProjectController extends ApiController
{
    public function __construct(
        private readonly PhotoDeliveryService $photoService,
        private readonly BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projects = $this->photoService->paginate($request->all());

        return $this->success(
            PhotoProjectResource::collection($projects)->response()->getData(true)
        );
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid, ['booking.user', 'lab', 'files']);
        $this->photoService->assertAccess($project, $request->user());

        $project = $this->photoService->checkExpiry($project);

        return $this->success(new PhotoProjectResource($project));
    }

    /**
     * Buka photo project baru untuk sebuah booking (booking harus completed).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'booking_uuid'  => ['required', 'string', 'exists:bookings,uuid'],
            'max_selection' => ['nullable', 'integer', 'min:0'],
        ]);

        $booking = $this->bookingService->findByUuid($request->input('booking_uuid'));
        $project = $this->photoService->createForBooking(
            $booking,
            (int) $request->input('max_selection', 0)
        );

        return $this->created(new PhotoProjectResource($project), 'Photo project berhasil dibuat.');
    }

    public function updateMaxSelection(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'max_selection' => ['required', 'integer', 'min:0'],
        ]);

        $project = $this->photoService->findByUuid($uuid);
        $project = $this->photoService->updateMaxSelection($project, (int) $request->input('max_selection'));

        return $this->success(new PhotoProjectResource($project), 'Kuota foto berhasil diupdate.');
    }
}
