<?php

declare(strict_types=1);

namespace App\Domain\LabService\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\LabService\Models\Package;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PackageService extends BaseService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Package::query()->with(['lab', 'items.service', 'items.asset']);

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
        $package = Package::with(['lab', 'items.service', 'items.asset'])
            ->where('uuid', $uuid)
            ->first();

        if (!$package) {
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
            $this->syncItems($package, $items);

            return $package->load(['lab', 'items.service', 'items.asset']);
        });
    }

    public function update(string $uuid, array $data): Package
    {
        return DB::transaction(function () use ($uuid, $data) {
            $package = $this->findByUuid($uuid);

            $items = $data['items'] ?? null;
            unset($data['items']);

            if (!empty($data)) {
                $package->update($data);
            }

            if ($items !== null) {
                $package->items()->delete();
                $this->syncItems($package, $items);
            }

            return $package->fresh(['lab', 'items.service', 'items.asset']);
        });
    }

    public function updateImage(string $uuid, UploadedFile $file): Package
{
    $package = $this->findByUuid($uuid);

    if ($package->image) {
        Storage::disk(Package::IMAGE_DISK)->delete($package->image);
    }

    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
    $path = "packages/{$package->lab_id}/{$filename}";

    Storage::disk(Package::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

    $package->update(['image' => $path]);

    return $package->fresh(['lab', 'items.service', 'items.asset']);
}

    public function delete(string $uuid): void
    {
        $package = $this->findByUuid($uuid);
        $package->delete();
    }

    private function syncItems(Package $package, array $items): void
    {
        foreach ($items as $item) {
            $package->items()->create([
                'service_id'        => $item['service_id'] ?? null,
                'asset_id'          => $item['asset_id'] ?? null,
                'quantity'          => $item['quantity'] ?? 1,
                'duration_minutes'  => $item['duration_minutes'] ?? null,
                'notes'             => $item['notes'] ?? null,
            ]);
        }
    }
}