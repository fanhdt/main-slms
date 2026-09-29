<?php

declare(strict_types=1);

namespace App\Domain\LabService\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\LabService\Models\Service;
use App\Domain\LabService\Models\ServiceOption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ServiceService extends BaseService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Service::query()->with('lab');

        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function findByUuid(string $uuid, bool $withOptions = false): Service
    {
        $query = Service::with('lab')->where('uuid', $uuid);

        if ($withOptions) {
            $query->with(['options' => fn ($q) => $q->orderBy('order')]);
        }

        $service = $query->first();

        if (! $service) {
            throw ApiException::notFound('Service');
        }

        return $service;
    }

    public function create(array $data): Service
    {
        $service = Service::create($data);

        return $service->load('lab');
    }

    public function update(string $uuid, array $data): Service
    {
        $service = $this->findByUuid($uuid);

        if (! empty($data)) {
            $service->update($data);
        }

        return $service->fresh('lab');
    }

    public function updateImage(string $uuid, UploadedFile $file): Service
    {
        $service = $this->findByUuid($uuid);

        if ($service->image) {
            Storage::disk(Service::IMAGE_DISK)->delete($service->image);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "services/{$service->uuid}/{$filename}";

        Storage::disk(Service::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

        $service->update(['image' => $path]);

        return $service->fresh();
    }

    public function delete(string $uuid): void
    {
        $service = $this->findByUuid($uuid);
        $service->delete();
    }

    // ============================================================
    // Service Options
    // ============================================================

    public function listOptions(string $serviceUuid): Collection
    {
        $service = $this->findByUuid($serviceUuid);

        return $service->options()->orderBy('order')->get();
    }

    public function createOption(string $serviceUuid, array $data): ServiceOption
    {
        $service = $this->findByUuid($serviceUuid);

        return $service->options()->create($data);
    }

    public function updateOption(string $serviceUuid, string $optionUuid, array $data): ServiceOption
    {
        $service = $this->findByUuid($serviceUuid);

        $option = $service->options()->where('uuid', $optionUuid)->first();

        if (! $option) {
            throw ApiException::notFound('Opsi layanan');
        }

        if (! empty($data)) {
            $option->update($data);
        }

        return $option->fresh();
    }

    public function deleteOption(string $serviceUuid, string $optionUuid): void
    {
        $service = $this->findByUuid($serviceUuid);

        $option = $service->options()->where('uuid', $optionUuid)->first();

        if (! $option) {
            throw ApiException::notFound('Opsi layanan');
        }

        $option->delete();
    }
}