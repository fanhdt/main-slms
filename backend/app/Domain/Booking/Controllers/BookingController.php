<?php

declare(strict_types=1);

namespace App\Domain\Booking\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\Requests\CreateBookingRequest;
use App\Domain\Booking\Resources\BookingResource;
use App\Domain\Booking\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends ApiController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->paginate($request->all());

        return $this->success(
            BookingResource::collection($bookings)->response()->getData(true)
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $booking = $this->bookingService->findByUuid($uuid);

        return $this->success(new BookingResource($booking));
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->getMyBookings(
            $request->user()->id,
            $request->all()
        );

        return $this->success(
            BookingResource::collection($bookings)->response()->getData(true)
        );
    }

    public function store(CreateBookingRequest $request): JsonResponse
    {
        // DTO otomatis mengambil user ID dari token yang sedang login!
        $dto = CreateBookingDTO::fromRequest(
            $request->validated(),
            $request->user()->id
        );

        $booking = $this->bookingService->create($dto);

        return $this->created(new BookingResource($booking), 'Booking berhasil dibuat.');
    }

    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string']
        ]);

        $booking = $this->bookingService->updateStatus($uuid, $request->input('status'));

        return $this->success(new BookingResource($booking), 'Status booking berhasil diupdate.');
    }

    public function updatePaymentStatus(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'payment_status' => ['required', 'string']
        ]);

        $booking = $this->bookingService->updatePaymentStatus($uuid, $request->input('payment_status'));

        return $this->success(new BookingResource($booking), 'Status pembayaran berhasil diperbarui.');
    }

    public function addAsset(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id']
        ]);

        $booking = $this->bookingService->addAsset($uuid, $request->input('asset_id'));

        return $this->success(new BookingResource($booking), 'Aset berhasil ditambahkan ke booking.');
    }

    /**
     * Check-in booking lewat scan QR code (dipanggil dari frontend scanner).
     */
    public function checkin(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $booking = $this->bookingService->checkin($request->input('code'));

        return $this->success(new BookingResource($booking), 'Check-in berhasil.');
    }
}