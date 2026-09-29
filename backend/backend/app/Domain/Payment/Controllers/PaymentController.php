<?php

declare(strict_types=1);

namespace App\Domain\Payment\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly BookingService $bookingService,
    ) {}

    /**
     * POST /bookings/{uuid}/pay
     */
    public function createSnapToken(string $uuid): JsonResponse
    {
        $booking = $this->bookingService->findByUuid($uuid);
        $snapToken = $this->paymentService->createSnapToken($booking);

        return $this->success([
            'snap_token' => $snapToken,
            'client_key' => config('midtrans.client_key'),
        ]);
    }

    /**
     * POST /payments/notification — webhook publik dari Midtrans (tanpa auth:sanctum).
     */
    public function notification(Request $request): JsonResponse
    {
        $this->paymentService->handleNotification();

        return $this->successMessage('OK');
    }
}