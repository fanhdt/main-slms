<?php

declare(strict_types=1);

namespace App\Domain\User\Controllers;

use App\Core\Exceptions\ApiException;
use App\Core\Http\Controllers\ApiController;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Resources\BookingResource;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Lab\Models\Lab;
use App\Domain\User\Models\User;
use App\Domain\User\Requests\AssignRfidRequest;
use App\Domain\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RfidController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly BookingService $bookingService,
    ) {}

    /**
     * Admin kaitkan UID kartu ke user tertentu.
     * PATCH /users/{uuid}/rfid
     */
    public function assign(AssignRfidRequest $request, string $uuid): JsonResponse
    {
        $user = $this->userService->assignRfid($uuid, $request->validated('rfid_uid'));

        return $this->success([
            'uuid'     => $user->uuid,
            'name'     => $user->name,
            'rfid_uid' => $user->rfid_uid,
        ], 'Kartu RFID berhasil dikaitkan.');
    }

    /**
     * Lookup riwayat booking berdasarkan UID kartu.
     * GET /users/rfid/{uid}/bookings
     */
    public function lookupBookings(string $uid): JsonResponse
    {
        $user = $this->userService->findByRfid($uid);

        $bookings = $this->bookingService->getMyBookings($user->id, ['per_page' => 20]);

        return $this->success([
            'user_name' => $user->name,
            'user_uuid' => $user->uuid,
            'bookings'  => BookingResource::collection($bookings->items()),
        ]);
    }

    /**
     * Tap kartu di komputer lab: login + booking gratis otomatis kalau jam kuliah,
     * lalu return riwayat booking khusus lab ini.
     * POST /labs/{slug}/rfid-login
     */
    public function loginAndCheckin(Request $request, string $slug): JsonResponse
    {
        $request->validate(['rfid_uid' => ['required', 'string']]);

        $lab = Lab::where('slug', $slug)->firstOrFail();
        $user = $this->userService->findByRfid($request->input('rfid_uid'));

        $token = $user->createToken('rfid-kiosk-' . $lab->slug)->plainTextToken;

        $window = $this->findActiveClassWindow($lab);
        $bookingCreated = null;
        $message = 'Login berhasil.';

        if ($window) {
            try {
                $bookingCreated = $this->createFreeClassBooking($user, $lab, $window);
                $message = 'Login berhasil, lab dipinjam gratis sampai ' . $window['end'] . '.';
            } catch (ApiException $e) {
                $message = 'Login berhasil, tapi booking gagal: ' . $e->getMessage();
            }
        }

        $bookings = $this->bookingService->getMyBookings($user->id, [
            'lab_id'   => $lab->id,
            'per_page' => 20,
        ]);

        return $this->success([
            'token'     => $token,
            'user_name' => $user->name,
            'booking'   => $bookingCreated,
            'bookings'  => BookingResource::collection($bookings->items()),
        ], $message);
    }

    private function findActiveClassWindow(Lab $lab): ?array
    {
        $classHours = $lab->settings['class_hours'] ?? [];
        $now = Carbon::now();
        $today = strtolower($now->format('l'));

        foreach ($classHours as $window) {
            if ($window['day'] !== $today) {
                continue;
            }

            $start = Carbon::createFromTimeString($window['start']);
            $end = Carbon::createFromTimeString($window['end']);

            if ($now->between($start, $end)) {
                return $window;
            }
        }

        return null;
    }

    /**
     * @throws ApiException
     */
    private function createFreeClassBooking(User $user, Lab $lab, array $window): Booking
    {
        $now = Carbon::now();
        $endOfClass = Carbon::createFromTimeString($window['end']);

        $conflict = Booking::where('lab_id', $lab->id)
            ->whereIn('status', [BookingStatus::Approved, BookingStatus::Ongoing])
            ->where('start_time', '<', $endOfClass)
            ->where('end_time', '>', $now)
            ->exists();

        if ($conflict) {
            throw ApiException::conflict('Lab sedang dipakai booking lain di jam ini.');
        }

        return Booking::create([
            'lab_id'         => $lab->id,
            'user_id'        => $user->id,
            'booking_code'   => 'BK-' . $now->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'booking_type'   => 'lab_rental',
            'purpose'        => 'academic',
            'start_time'     => $now,
            'end_time'       => $endOfClass,
            'status'         => BookingStatus::Approved,
            'payment_status' => PaymentStatus::Paid,
            'total_price'    => 0,
            'notes'          => 'Booking otomatis via RFID — jam kuliah.',
        ]);
    }
}