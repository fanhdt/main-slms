<?php

declare(strict_types=1);

namespace App\Domain\Asset\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Asset\DTOs\CreateAssetDTO;
use App\Domain\Asset\DTOs\UpdateAssetDTO;
use App\Domain\Asset\Models\Asset;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetService extends BaseService
{
    /**
     * Ambil semua aset dengan pagination dan filter.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Asset::query()->with('lab');

        // Filter by lab — wajib ada untuk isolasi data antar lab
        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        // Filter by kategori
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by rentable
        if (isset($filters['is_rentable'])) {
            $query->where('is_rentable', $filters['is_rentable']);
        }

        // Search by nama atau kode
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'ilike', '%' . $filters['search'] . '%')
                  ->orWhere('code', 'ilike', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Ambil satu aset berdasarkan UUID.
     *
     * @throws ApiException
     */
    public function findByUuid(string $uuid): Asset
    {
        $asset = Asset::with('lab')->where('uuid', $uuid)->first();

        if (! $asset) {
            throw ApiException::notFound('Asset');
        }

        return $asset;
    }

    /**
     * Buat aset baru.
     */
    public function create(CreateAssetDTO $dto): Asset
{
    $asset = Asset::create([
        'lab_id'         => $dto->labId,
        'name'           => $dto->name,
        'code'           => $dto->code,
        'category'       => $dto->category,
        'brand'          => $dto->brand,
        'model'          => $dto->model,
        'description'    => $dto->description,
        'serial_number'  => $dto->serialNumber,
        'status'         => $dto->status,
        'purchase_price' => $dto->purchasePrice,
        'purchase_date'  => $dto->purchaseDate,
        'specifications' => $dto->specifications,
        'image'          => $dto->image,
        'is_rentable'    => $dto->isRentable,
        'rental_price'   => $dto->rentalPrice,
        'quantity'       => $dto->quantity, 
    ]);

    return $asset->load('lab');
}

    /**
     * Update aset berdasarkan UUID.
     *
     * @throws ApiException
     */
    public function update(string $uuid, UpdateAssetDTO $dto): Asset
    {
        $asset = $this->findByUuid($uuid);

        $data = $dto->toArray();

        if (! empty($data)) {
            $asset->update($data);
        }

        return $asset->fresh('lab');
    }

    /**
     * Hapus aset (soft delete).
     *
     * @throws ApiException
     */
    public function delete(string $uuid): void
    {
        $asset = $this->findByUuid($uuid);
        $asset->delete();
    }

    /**
     * Update status aset saja.
     *
     * @throws ApiException
     */
    public function updateStatus(string $uuid, string $status): Asset
    {
        $asset = $this->findByUuid($uuid);
        $asset->update(['status' => $status]);

        return $asset->fresh();
    }

    public function updateImage(string $uuid, UploadedFile $file): Asset
{
    $asset = $this->findByUuid($uuid);

    if ($asset->image) {
        Storage::disk(Asset::IMAGE_DISK)->delete($asset->image);
    }

    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
    $path = "assets/{$asset->lab_id}/{$filename}";

    Storage::disk(Asset::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

    $asset->update(['image' => $path]);

    return $asset->fresh('lab');
}
public function checkAvailability(array $assetUuids, string $startDate, string $endDate): array
{
    $assets = Asset::whereIn('uuid', $assetUuids)->get();

    $occupyingStatuses = [
        \App\Domain\Booking\Enums\BookingStatus::Pending->value,
        \App\Domain\Booking\Enums\BookingStatus::Approved->value,
        \App\Domain\Booking\Enums\BookingStatus::Ongoing->value,
    ];

    $start = \Carbon\Carbon::parse($startDate)->startOfDay();
    $end = \Carbon\Carbon::parse($endDate)->startOfDay();

    return $assets->map(function ($asset) use ($occupyingStatuses, $start, $end) {
        $reservedQty = (int) \App\Domain\Booking\Models\BookingAsset::query()
            ->where('asset_id', $asset->id)
            ->whereHas('booking', function ($q) use ($occupyingStatuses, $start, $end) {
                $q->whereIn('status', $occupyingStatuses)
                  ->where('start_time', '<=', $end)
                  ->where('end_time', '>=', $start);
            })
            ->sum('quantity');

        return [
            'asset_uuid'     => $asset->uuid,
            'total_quantity' => $asset->quantity,
            'reserved_qty'   => $reservedQty,
            'available_qty'  => max(0, $asset->quantity - $reservedQty),
        ];
    })->values()->toArray();
}

public function calculateAvailableNow(Asset $asset): int
{
    $now = now();

    $reservedQty = (int) \App\Domain\Booking\Models\BookingAsset::query()
        ->where('asset_id', $asset->id)
        ->whereNotIn('status', ['returned']) // sudah dikembalikan = tidak lagi menahan stok
        ->whereHas('booking', function ($q) use ($now) {
            $q->whereIn('status', [
                \App\Domain\Booking\Enums\BookingStatus::Pending->value,
                \App\Domain\Booking\Enums\BookingStatus::Approved->value,
                \App\Domain\Booking\Enums\BookingStatus::Ongoing->value,
            ])
            ->where('end_time', '>=', $now);
        })
        ->sum('quantity');

    return max(0, $asset->quantity - $reservedQty);
}
}