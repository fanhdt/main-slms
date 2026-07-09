<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Asset\Models\Asset;
use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\Enums\BookingPurpose;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\BookingType;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Booking\Events\BookingCheckedIn;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Events\BookingStatusChanged;
use App\Domain\Booking\Models\Booking;
use App\Domain\Lab\Models\Lab;
use App\Domain\LabService\Models\Package;
use App\Domain\LabService\Models\Service;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use Carbon\Carbon;
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
        $booking = Booking::with(['items.service', 'items.package', 'user', 'lab', 'assets.asset'])
            ->where('uuid', $uuid)
            ->first();

        if (!$booking) {
            throw new NotFoundHttpException('Booking tidak ditemukan.');
        }

        return $booking;
    }

    public function findByCode(string $code): Booking
    {
        $booking = Booking::with(['items.service', 'items.package', 'user', 'lab', 'assets.asset'])
            ->where('booking_code', $code)
            ->first();

        if (!$booking) {
            throw ApiException::notFound('Booking dengan kode tersebut');
        }

        return $booking;
    }

    public function create(CreateBookingDTO $dto): Booking
    {
        $booking = match ($dto->bookingType) {
            BookingType::LabRental  => $this->createLabRental($dto),
            BookingType::AssetRental => $this->createAssetRental($dto),
            BookingType::Service     => $this->createServiceBooking($dto),
        };

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

    /**
     * Pinjam Lab — tanpa item, harga dihitung dari purpose (akademik/organisasi/umum) x durasi.
     */
    private function createLabRental(CreateBookingDTO $dto): Booking
    {
        $purpose = BookingPurpose::from($dto->purpose);
        $user = User::findOrFail($dto->userId);
        $lab = Lab::findOrFail($dto->labId);

        // Simpan NIM kalau user belum punya dan baru submit sekarang
        if ($purpose->requiresStudent()) {
            if (!$user->nim && $dto->nim) {
                $user->update(['nim' => $dto->nim]);
                $user->refresh();
            }
            if (!$user->nim) {
                throw ApiException::unprocessable(
                    $dto->nim
                        ? 'Gagal menyimpan NIM. Silakan coba lagi atau hubungi admin.'
                        : 'Keperluan akademik/organisasi hanya untuk mahasiswa. Isi NIM terlebih dahulu.'
                );
            }
        }

        $start = Carbon::parse($dto->startTime);
        $end = Carbon::parse($dto->endTime);
        $hours = max(1, (int) ceil($start->diffInMinutes($end) / 60));
        $ratePerHour = $lab->rentalRatePerHour($purpose);
        $totalPrice = $ratePerHour * $hours;

        return DB::transaction(function () use ($dto, $purpose, $totalPrice) {
            $booking = Booking::create([
                'lab_id'         => $dto->labId,
                'user_id'        => $dto->userId,
                'booking_code'   => $this->generateBookingCode(),
                'booking_type'   => BookingType::LabRental->value,
                'purpose'        => $purpose->value,
                'start_time'     => $dto->startTime,
                'end_time'       => $dto->endTime,
                'status'         => BookingStatus::Pending,
                'payment_status' => $totalPrice > 0 ? PaymentStatus::Unpaid : PaymentStatus::Paid,
                'total_price'    => $totalPrice,
                'notes'          => $dto->notes,
            ]);

            return $booking->load(['user', 'lab']);
        });
    }

    /**
     * Sewa Alat — pilih dari Asset yang is_rentable, harga per hari x jumlah hari sewa.
     * Rentang tanggal dianggap inklusif (10-12 Juli = 3 hari), bukan per jam-slot.
     */
    private function createAssetRental(CreateBookingDTO $dto): Booking
    {
        $assets = Asset::whereIn('id', $dto->assetIds)->get();

        if ($assets->count() !== count($dto->assetIds)) {
            throw ApiException::unprocessable('Ada aset yang dipilih tidak ditemukan.');
        }

        $notRentable = $assets->firstWhere('is_rentable', false);
        if ($notRentable) {
            throw ApiException::unprocessable("Aset \"{$notRentable->name}\" tidak tersedia untuk disewa.");
        }

        $start = Carbon::parse($dto->startTime)->startOfDay();
        $end = Carbon::parse($dto->endTime)->startOfDay();
        $rentalDays = max(1, $start->diffInDays($end) + 1);

        $totalPrice = $assets->sum('rental_price') * $rentalDays;

        return DB::transaction(function () use ($dto, $assets, $totalPrice, $rentalDays) {
            $booking = Booking::create([
                'lab_id'         => $dto->labId,
                'user_id'        => $dto->userId,
                'booking_code'   => $this->generateBookingCode(),
                'booking_type'   => BookingType::AssetRental->value,
                'start_time'     => $dto->startTime,
                'end_time'       => $dto->endTime,
                'status'         => BookingStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'total_price'    => $totalPrice,
                'notes'          => $dto->notes,
            ]);

            foreach ($assets as $asset) {
                $booking->assets()->create([
                    'asset_id'    => $asset->id,
                    'rental_days' => $rentalDays,
                    'subtotal'    => $asset->rental_price * $rentalDays,
                    'status'      => 'reserved',
                ]);
            }

            return $booking->load(['user', 'lab', 'assets.asset']);
        });
    }

    /**
     * Jasa & Paket — alur booking yang sudah ada sebelumnya (Service/Package).
     */
    private function createServiceBooking(CreateBookingDTO $dto): Booking
    {
        return DB::transaction(function () use ($dto) {
            $totalPrice = 0;
            $bookingItemsData = [];

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

            $booking = Booking::create([
                'lab_id'         => $dto->labId,
                'user_id'        => $dto->userId,
                'booking_code'   => $this->generateBookingCode(),
                'booking_type'   => BookingType::Service->value,
                'start_time'     => $dto->startTime,
                'end_time'       => $dto->endTime,
                'status'         => BookingStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'total_price'    => $totalPrice,
                'notes'          => $dto->notes,
            ]);

            $booking->items()->createMany($bookingItemsData);

            return $booking->load(['items', 'user', 'lab']);
        });
    }

    private function generateBookingCode(): string
    {
        $dateCode = now()->format('Ymd');
        $randomStr = strtoupper(Str::random(4));

        return "BK-{$dateCode}-{$randomStr}";
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

       public function cancelByOwner(string $uuid, int $userId): Booking
   {
     $booking = $this->findByUuid($uuid);

     if ($booking->user_id !== $userId) {
       throw ApiException::forbidden('Kamu tidak bisa membatalkan booking milik orang lain.');
     }

     if (!in_array($booking->status, [BookingStatus::Pending, BookingStatus::Approved], true)) {
       throw ApiException::unprocessable(
         "Booking dengan status \"{$booking->status->label()}\" tidak bisa dibatalkan lagi."
       );
     }

     if (now()->greaterThanOrEqualTo($booking->start_time)) {
       throw ApiException::unprocessable('Booking tidak bisa dibatalkan karena waktu mulai sudah lewat.');
     }

     $previousStatus = $booking->status->value;
     $booking->update(['status' => BookingStatus::Canceled]);

     // Notifikasi ke staff lab (bukan ke user, karena usernya sendiri yang cancel)
     $this->notificationService->notifyLabStaff(
       labId: $booking->lab_id,
       type: 'BookingCanceledByUser',
       title: 'Booking dibatalkan pengguna',
       body: "{$booking->booking_code} dibatalkan oleh {$booking->user->name}.",
       data: ['booking_uuid' => $booking->uuid],
     );
     event(new BookingStatusChanged($booking, $previousStatus));

     return $booking;  }

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

        $booking = $booking->fresh(['items.service', 'items.package', 'user', 'lab', 'assets.asset']);

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

        if ($booking->assets()->where('asset_id', $assetId)->exists()) {
            throw new \InvalidArgumentException('Aset ini sudah dipinjam di booking ini.');
        }

        $booking->assets()->create([
            'asset_id' => $assetId,
            'status'   => 'borrowed',
        ]);

        return $booking->load(['items', 'assets']);
    }

    public function getMyBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::query()
            ->with(['user', 'lab', 'items.service', 'items.package', 'assets.asset', 'photoProject'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $statuses = is_string($filters['status'])
         ? explode(',', $filters['status'])
         : (array) $filters['status'];
       $query->whereIn('status', $statuses);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 10);
    }
}