<?php

declare(strict_types=1);

namespace App\Domain\LabService\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\LabService\Models\Package;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PackageService extends BaseService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Package::query()->with(['lab', 'items.service']);

        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_custom'])) {
            $query->where('is_custom', $filters['is_custom']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function findByUuid(string $uuid): Package
    {
        $package = Package::with(['lab', 'items.service'])
            ->where('uuid', $uuid)
            ->first();

        if (! $package) {
            throw ApiException::notFound('Package');
        }

        return $package;
    }

    public function create(array $data): Package
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $package = Package::create($data);

            // Simpan items jika ada
            foreach ($items as $item) {
                $package->items()->create([
                    'service_id' => $item['service_id'],
                    'quantity'   => $item['quantity'] ?? 1,
                    'notes'      => $item['notes'] ?? null,
                ]);
            }

            return $package->load(['lab', 'items.service']);
        });
    }

    public function update(string $uuid, array $data): Package
    {
        return DB::transaction(function () use ($uuid, $data) {
            $package = $this->findByUuid($uuid);

            $items = $data['items'] ?? null;
            unset($data['items']);

            if (! empty($data)) {
                $package->update($data);
            }

            // Update items jika dikirim — replace semua
            if ($items !== null) {
                $package->items()->delete();

                foreach ($items as $item) {
                    $package->items()->create([
                        'service_id' => $item['service_id'],
                        'quantity'   => $item['quantity'] ?? 1,
                        'notes'      => $item['notes'] ?? null,
                    ]);
                }
            }

            return $package->fresh(['lab', 'items.service']);
        });
    }

    public function delete(string $uuid): void
    {
        $package = $this->findByUuid($uuid);
        $package->delete();
    }
}