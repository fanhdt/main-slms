<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Booking\Events\BookingCheckedIn;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Events\BookingStatusChanged;
use App\Domain\Booking\Models\Booking;
use App\Domain\LabService\Models\Package;
use App\Domain\LabService\Models\Service;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingService extends BaseService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Booking::query()->with(['user', 'lab', 'photoProject']);

        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findByUuid(string $uuid): Booking
    {
        $booking = Booking::with(['items.service', 'items.package', 'user', 'lab'])
            ->where('uuid', $uuid)
            ->first();

        if (!$booking) {
            throw new NotFoundHttpException('Booking tidak ditemukan.');
        }

        return $booking;
    }

    public function findByCode(string $code): Booking
    {
        $booking = Booking::with(['items.service', 'items.package', 'user', 'lab', 'assets'])
            ->where('booking_code', $code)
            ->first();

        if (!$booking) {
            throw ApiException::notFound('Booking dengan kode tersebut');
        }

        return $booking;
    }

    public function create(CreateBookingDTO $dto): Booking
    {
        $booking = DB::transaction(function () use ($dto) {
            $totalPrice = 0;
            $bookingItemsData = [];

            // 1. Hitung harga otomatis dan siapkan data items
            foreach ($dto->items as $item) {
                $price = 0;

                if (!empty($item['service_id'])) {
                    $service = Service::findOrFail($item['service_id']);
                    $price = $service->price;
                } elseif (!empty($item['package_id'])) {
                    $package = Package::findOrFail($item['package_id']);
                    $price = $package->price - $package->discount;
                }

                $subtotal = $price * $item['quantity'];
                $totalPrice += $subtotal;

                $bookingItemsData[] = [
                    'service_id' => $item['service_id'] ?? null,
                    'package_id' => $item['package_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'price'      => $price,
                    'subtotal'   => $subtotal,
                ];
            }

            // 2. Generate Booking Code unik (Contoh: BK-20260628-X8Y1)
            $dateCode = now()->format('Ymd');
            $randomStr = strtoupper(Str::random(4));
            $bookingCode = "BK-{$dateCode}-{$randomStr}";

            // 3. Buat record Booking utama
            $booking = Booking::create([
                'lab_id'         => $dto->labId,
                'user_id'        => $dto->userId,
                'booking_code'   => $bookingCode,
                'start_time'     => $dto->startTime,
                'end_time'       => $dto->endTime,
                'status'         => BookingStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'total_price'    => $totalPrice,
                'notes'          => $dto->notes,
            ]);

            // 4. Simpan relasi items
            $booking->items()->createMany($bookingItemsData);

            return $booking->load(['items', 'user', 'lab']);
        });

        // Notifikasi + broadcast ke staff lab: ada booking baru masuk
        $this->notificationService->notifyLabStaff(
            labId: $booking->lab_id,
            type: 'BookingCreated',
            title: 'Booking baru masuk',
            body: "{$booking->user?->name} membuat booking {$booking->booking_code}.",
            data: ['booking_uuid' => $booking->uuid],
        );
        event(new BookingCreated($booking));

        return $booking;
    }

    public function updateStatus(string $uuid, string $status): Booking
    {
        $booking = $this->findByUuid($uuid);

        $validStatuses = array_column(BookingStatus::cases(), 'value');
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Status tidak valid.');
        }

        $previousStatus = $booking->status->value;
        $booking->update(['status' => $status]);

        // Notifikasi + broadcast ke customer: status booking-nya berubah
        if ($previousStatus !== $status) {
            $this->notificationService->notifyUser(
                userId: $booking->user_id,
                type: 'BookingStatusChanged',
                title: 'Status booking diperbarui',
                body: "Booking {$booking->booking_code} sekarang: {$booking->status->label()}.",
                data: ['booking_uuid' => $booking->uuid, 'status' => $status],
                labId: $booking->lab_id,
            );
            event(new BookingStatusChanged($booking, $previousStatus));
        }

        return $booking;
    }

    public function updatePaymentStatus(string $uuid, string $paymentStatus): Booking
    {
        $booking = $this->findByUuid($uuid);

        $validStatuses = array_column(PaymentStatus::cases(), 'value');
        if (!in_array($paymentStatus, $validStatuses)) {
            throw new \InvalidArgumentException('Status pembayaran tidak valid.');
        }

        $booking->update(['payment_status' => $paymentStatus]);

        return $booking;
    }

    /**
     * Check-in booking via QR scan (kode booking).
     * Hanya booking berstatus "approved" yang bisa di-checkin.
     */
    public function checkin(string $code): Booking
    {
        $booking = $this->findByCode($code);

        if ($booking->status === BookingStatus::Ongoing) {
            throw ApiException::conflict('Booking ini sudah check-in sebelumnya.');
        }

        if ($booking->status !== BookingStatus::Approved) {
            throw ApiException::unprocessable(
                "Booking tidak bisa di-checkin. Status saat ini: {$booking->status->label()}."
            );
        }

        $booking->update([
            'status'        => BookingStatus::Ongoing,
            'checked_in_at' => now(),
        ]);

        $booking = $booking->fresh(['items.service', 'items.package', 'user', 'lab', 'assets']);

        // Notifikasi ke staff lab lain (kalau checkin dari device berbeda)
        $this->notificationService->notifyLabStaff(
            labId: $booking->lab_id,
            type: 'BookingCheckedIn',
            title: 'Booking di-checkin',
            body: "Booking {$booking->booking_code} baru saja di-checkin.",
            data: ['booking_uuid' => $booking->uuid],
        );
        event(new BookingCheckedIn($booking));

        return $booking;
    }

    public function addAsset(string $uuid, int $assetId): Booking
    {
        $booking = $this->findByUuid($uuid);

        // Cek apakah aset sudah ada di booking ini untuk mencegah duplikasi
        if ($booking->assets()->where('asset_id', $assetId)->exists()) {
            throw new \InvalidArgumentException('Aset ini sudah dipinjam di booking ini.');
        }

        $booking->assets()->create([
            'asset_id' => $assetId,
            'status'   => 'borrowed', // Status default saat alat diserahkan
        ]);

        return $booking->load(['items', 'assets']);
    }

    public function getMyBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::query()
            ->with(['user', 'lab', 'items.service', 'items.package', 'photoProject'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->whereHas('user', fn ($q) => $q->where('uuid', $filters['user_id']));
        }

        if (isset($filters['own_only']) && $filters['own_only']) {
            $query->where('user_id', $filters['user_id_int']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 10);
    }
}
