<?php

declare(strict_types=1);

namespace App\Domain\LabService\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\LabService\Models\Service;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function findByUuid(string $uuid): Service
    {
        $service = Service::with('lab')->where('uuid', $uuid)->first();

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

    public function delete(string $uuid): void
    {
        $service = $this->findByUuid($uuid);
        $service->delete();
    }
}