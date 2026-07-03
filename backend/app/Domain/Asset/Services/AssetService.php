<?php

declare(strict_types=1);

namespace App\Domain\Asset\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Asset\DTOs\CreateAssetDTO;
use App\Domain\Asset\DTOs\UpdateAssetDTO;
use App\Domain\Asset\Models\Asset;
use Illuminate\Pagination\LengthAwarePaginator;

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
}