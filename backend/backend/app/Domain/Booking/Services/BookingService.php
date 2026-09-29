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
use App\Domain\LabService\Enums\ServiceOptionPriceType;
use App\Domain\LabService\Models\Package;
use App\Domain\LabService\Models\Service;
use App\Domain\LabService\Models\ServiceOption;
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
        $booking = Booking::with(['items.service', 'items.package', 'items.options', 'user', 'lab', 'assets.asset'])
            ->where('uuid', $uuid)
            ->first();

        if (!$booking) {
            throw new NotFoundHttpException('Booking tidak ditemukan.');
        }

        return $booking;
    }

    public function findByCode(string $code): Booking
    {
        $booking = Booking::with(['items.service', 'items.package', 'items.options', 'user', 'lab', 'assets.asset'])
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
     */
    /**
 * Sewa Alat — pilih dari Asset yang is_rentable, harga per hari x jumlah hari sewa.
 */
private function createAssetRental(CreateBookingDTO $dto): Booking
{
    $assetIds = collect($dto->assets)->pluck('asset_id')->toArray();
    $assets = Asset::whereIn('id', $assetIds)->get()->keyBy('id');

    if ($assets->count() !== count($assetIds)) {
        throw ApiException::unprocessable('Ada aset yang dipilih tidak ditemukan.');
    }

    $notRentable = $assets->firstWhere('is_rentable', false);
    if ($notRentable) {
        throw ApiException::unprocessable("Aset \"{$notRentable->name}\" tidak tersedia untuk disewa.");
    }

    $start = Carbon::parse($dto->startTime)->startOfDay();
    $end = Carbon::parse($dto->endTime)->startOfDay();
    $rentalDays = max(1, $start->diffInDays($end) + 1);

    // Cegah overbooking: cek stok yang sudah "ditahan" booking lain yang
    // statusnya masih occupying (pending/approved/ongoing) dan tanggalnya overlap.
    $this->assertAssetAvailability($dto->assets, $assets, $start, $end);

    $totalPrice = 0;
    foreach ($dto->assets as $item) {
        $asset = $assets->get($item['asset_id']);
        $totalPrice += $asset->rental_price * $item['quantity'] * $rentalDays;
    }

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

        foreach ($dto->assets as $item) {
            $asset = $assets->get($item['asset_id']);
            $booking->assets()->create([
                'asset_id'    => $asset->id,
                'quantity'    => $item['quantity'],
                'rental_days' => $rentalDays,
                'subtotal'    => $asset->rental_price * $item['quantity'] * $rentalDays,
                'status'      => 'reserved',
            ]);
        }

        return $booking->load(['user', 'lab', 'assets.asset']);
    });
}

/**
 * Cek ketersediaan stok tiap aset untuk rentang tanggal yang diminta.
 * Booking lain dengan status pending/approved/ongoing dianggap "menahan" stok
 * (belum tentu jadi, tapi selama belum ditolak/dibatalkan, stoknya harus diblok
 * supaya tidak ada 2 booking yang sama-sama dapat alat yang sama di tanggal sama).
 *
 * @throws ApiException
 */
private function assertAssetAvailability(array $requestedAssets, \Illuminate\Support\Collection $assets, Carbon $start, Carbon $end): void
{
    $occupyingStatuses = [
        BookingStatus::Pending->value,
        BookingStatus::Approved->value,
        BookingStatus::Ongoing->value,
    ];

    foreach ($requestedAssets as $item) {
        $asset = $assets->get($item['asset_id']);
        $requestedQty = $item['quantity'];

        $reservedQty = (int) \App\Domain\Booking\Models\BookingAsset::query()
            ->where('asset_id', $asset->id)
            ->whereHas('booking', function ($q) use ($occupyingStatuses, $start, $end) {
                $q->whereIn('status', $occupyingStatuses)
                  ->where('start_time', '<=', $end)
                  ->where('end_time', '>=', $start);
            })
            ->sum('quantity');

        if ($reservedQty + $requestedQty > $asset->quantity) {
            $available = max(0, $asset->quantity - $reservedQty);
            throw ApiException::unprocessable(
                "Stok \"{$asset->name}\" tidak cukup untuk tanggal yang dipilih. " .
                "Tersisa {$available} unit dari total {$asset->quantity} unit."
            );
        }
    }
}

    /**
     * Jasa & Paket — sekarang mendukung opsi tambahan per Service
     * (misal Edit Foto -> Retouch, Remove BG, Color Grading).
     */
    private function createServiceBooking(CreateBookingDTO $dto): Booking
{
    return DB::transaction(function () use ($dto) {
        $totalPrice = 0;
        $bookingItemsData = [];
        $maxDurationMinutes = 60 * 24 * 3; // fallback: 3 hari

        // NEW — kumpulkan catatan custom dari semua item, digabung ke notes booking
        $customNotes = collect($dto->items)
            ->filter(fn ($item) => !empty($item['custom_note']))
            ->map(fn ($item) => $item['custom_note'])
            ->implode(' | ');

        foreach ($dto->items as $item) {
            $price = 0;
            $durationMinutes = null;
            $optionRows = [];

            if (!empty($item['service_id'])) {
            $service = Service::findOrFail($item['service_id']);

            // Editing Custom (is_custom_pricing): harga tetap, ditentukan admin saat
            // membuat layanan — dipakai apa adanya, sama seperti layanan biasa lain.
            $price = (float) $service->price;
            $durationMinutes = $service->duration;

            // Opsi tambahan (Retouch, Remove BG, dst) HANYA berlaku untuk layanan
            // non-custom, misal Editing Dasar yang harganya murni dari pilihan ini.
            if (!$service->is_custom_pricing) {
                $optionIds = $item['option_ids'] ?? [];
                if (!empty($optionIds)) {
                    $options = ServiceOption::where('service_id', $service->id)
                        ->whereIn('id', $optionIds)
                        ->get();

                    [$optionsTotal, $optionExtraMinutes, $optionRows] = $this->calculateOptions(
                        $options,
                        $durationMinutes,
                        $item['photo_count'] ?? null,
                        $item['custom_prices'] ?? []
                    );

                    $price += $optionsTotal;
                    if ($optionExtraMinutes > 0) {
                        $durationMinutes = ($durationMinutes ?? 0) + $optionExtraMinutes;
                    }
                }
            }
        } elseif (!empty($item['package_id'])) {
                $package = Package::findOrFail($item['package_id']);
                $price = (float) ($package->price - $package->discount);
                $durationMinutes = $package->duration;
            }

            if ($durationMinutes) {
                $maxDurationMinutes = max($maxDurationMinutes, $durationMinutes);
            }

            $subtotal = $price * $item['quantity'];
            $totalPrice += $subtotal;

            $bookingItemsData[] = [
                'service_id'   => $item['service_id'] ?? null,
                'package_id'   => $item['package_id'] ?? null,
                'quantity'     => $item['quantity'],
                'price'        => $price,
                'subtotal'     => $subtotal,
                'option_rows'  => $optionRows,
            ];
        }

        $startTime = $dto->startTime ?? now();
        $endTime = $dto->endTime ?? now()->addMinutes($maxDurationMinutes);

        // NEW — gabungkan notes asli dengan catatan permintaan custom
        $finalNotes = trim(
            ($dto->notes ?? '') . ($customNotes ? "\n[Permintaan Custom] {$customNotes}" : '')
        );

        $booking = Booking::create([
            'lab_id'         => $dto->labId,
            'user_id'        => $dto->userId,
            'booking_code'   => $this->generateBookingCode(),
            'booking_type'   => BookingType::Service->value,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'status'         => BookingStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'total_price'    => $totalPrice,
            'notes'          => $finalNotes ?: null,
        ]);

        foreach ($bookingItemsData as $itemData) {
            $optionRows = $itemData['option_rows'];
            unset($itemData['option_rows']);

            $bookingItem = $booking->items()->create($itemData);

            foreach ($optionRows as $row) {
                $bookingItem->options()->create($row);
            }
        }

        return $booking->load(['items.options', 'user', 'lab']);
    });
}

    /**
     * Hitung total harga dari opsi-opsi tambahan yang dipilih, sekaligus siapkan
     * data mentah untuk disimpan sebagai snapshot di booking_item_options.
     *
     * @return array{0: float, 1: int, 2: array} [totalHarga, totalExtraMinutes, optionRows]
     */
    private function calculateOptions(
    \Illuminate\Support\Collection $options,
    ?int $baseDurationMinutes,
    ?int $photoCount,
    array $customPrices = []
): array {
    $total = 0.0;
    $totalExtraMinutes = 0;
    $rows = [];

    $estimatedHours = max(1, (int) ceil(($baseDurationMinutes ?? 60) / 60));

    foreach ($options as $option) {
        $subtotal = match ($option->price_type) {
            ServiceOptionPriceType::Flat     => (float) $option->price,
            ServiceOptionPriceType::PerHour  => (float) $option->price * $estimatedHours,
            ServiceOptionPriceType::PerPhoto => (float) $option->price * max(1, $photoCount ?? 1),
            // NEW — custom: pakai harga yang diinput customer/staff, fallback ke 0 kalau tidak diisi
            ServiceOptionPriceType::Custom   => (float) ($customPrices[$option->id] ?? 0),
        };

        $total += $subtotal;
        $totalExtraMinutes += $option->extra_minutes ?? 0;

        $rows[] = [
            'service_option_id'      => $option->id,
            'name_snapshot'          => $option->name,
            'price_type_snapshot'    => $option->price_type->value,
            'price_snapshot'         => $option->price_type === ServiceOptionPriceType::Custom
                ? $subtotal
                : $option->price,
            'extra_minutes_snapshot' => $option->extra_minutes,
            'subtotal'               => $subtotal,
        ];
    }

    return [$total, $totalExtraMinutes, $rows];
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

        $this->notificationService->notifyLabStaff(
            labId: $booking->lab_id,
            type: 'BookingCanceledByUser',
            title: 'Booking dibatalkan pengguna',
            body: "{$booking->booking_code} dibatalkan oleh {$booking->user->name}.",
            data: ['booking_uuid' => $booking->uuid],
        );
        event(new BookingStatusChanged($booking, $previousStatus));

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

    // Kalau ini booking sewa alat, tandai semua item sebagai "sedang dipinjam"
    if ($booking->booking_type === BookingType::AssetRental) {
        $booking->assets()->update(['status' => 'borrowed']);
    }

    $booking = $booking->fresh(['items.service', 'items.package', 'items.options', 'user', 'lab', 'assets.asset']);

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
            ->with(['user', 'lab', 'items.service', 'items.package', 'items.options', 'assets.asset', 'photoProject'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $statuses = is_string($filters['status'])
                ? explode(',', $filters['status'])
                : (array) $filters['status'];
            $query->whereIn('status', $statuses);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 10);
    }

    public function verifyAssetReturn(string $bookingUuid, int $bookingAssetId, string $status, ?string $returnNotes = null): Booking
{
    $booking = $this->findByUuid($bookingUuid);

    if ($booking->booking_type !== BookingType::AssetRental) {
        throw ApiException::unprocessable('Booking ini bukan booking sewa alat.');
    }

    if ($booking->status !== BookingStatus::Ongoing) {
        throw ApiException::unprocessable(
            "Verifikasi pengembalian hanya bisa dilakukan saat booking sedang berlangsung (ongoing). Status saat ini: {$booking->status->label()}."
        );
    }

    $bookingAsset = $booking->assets()->where('id', $bookingAssetId)->first();
    if (!$bookingAsset) {
        throw ApiException::notFound('Item alat pada booking ini');
    }

    if (!in_array($status, ['returned', 'damaged'], true)) {
        throw new \InvalidArgumentException('Status pengembalian tidak valid.');
    }

    $bookingAsset->update([
        'status'       => $status,
        'return_notes' => $returnNotes,
    ]);

    // Kalau semua item sudah diverifikasi (returned/damaged), booking selesai otomatis.
    $stillPending = $booking->assets()->whereNotIn('status', ['returned', 'damaged'])->exists();
    if (!$stillPending) {
        $previousStatus = $booking->status->value;
        $booking->update(['status' => BookingStatus::Completed]);

        event(new BookingStatusChanged($booking->fresh(), $previousStatus));
    }

    return $booking->fresh(['assets.asset', 'user', 'lab']);
}
}