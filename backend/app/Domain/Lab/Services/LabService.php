<?php

declare(strict_types=1);

namespace App\Domain\Lab\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Lab\DTOs\CreateLabDTO;
use App\Domain\Lab\DTOs\UpdateLabDTO;
use App\Domain\Lab\Models\Lab;
use Illuminate\Pagination\LengthAwarePaginator;

class LabService extends BaseService
{
    /**
     * Ambil semua lab dengan pagination dan filter.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Lab::query();

        // Filter by status aktif
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Search by nama
        if (isset($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Ambil satu lab berdasarkan UUID.
     *
     * @throws ApiException
     */
    public function findByUuid(string $uuid): Lab
    {
        $lab = Lab::findByUuid($uuid);

        if (! $lab) {
            throw ApiException::notFound('Lab');
        }

        return $lab;
    }

    /**
     * Ambil satu lab berdasarkan slug (untuk public landing page).
     *
     * @throws ApiException
     */
    public function findBySlug(string $slug): Lab
    {
        $lab = Lab::where('slug', $slug)->where('is_active', true)->first();

        if (! $lab) {
            throw ApiException::notFound('Lab');
        }

        return $lab;
    }

    /**
     * Buat lab baru.
     */
    public function create(CreateLabDTO $dto): Lab
    {
        return Lab::create([
            'name'            => $dto->name,
            'slug'            => $dto->slug,
            'description'     => $dto->description,
            'primary_color'   => $dto->primaryColor,
            'secondary_color' => $dto->secondaryColor,
            'logo'            => $dto->logo,
            'hero_image'      => $dto->heroImage,
            'favicon'         => $dto->favicon,
            'contact'         => $dto->contact,
            'settings'        => $dto->settings,
            'is_active'       => $dto->isActive,
        ]);
    }

    /**
     * Update lab berdasarkan UUID.
     *
     * @throws ApiException
     */
    public function update(string $uuid, UpdateLabDTO $dto): Lab
    {
        $lab = $this->findByUuid($uuid);

        $data = $dto->toArray();

        if (! empty($data)) {
            $lab->update($data);
        }

        return $lab->fresh();
    }

    /**
     * Hapus lab (soft delete).
     *
     * @throws ApiException
     */
    public function delete(string $uuid): void
    {
        $lab = $this->findByUuid($uuid);
        $lab->delete();
    }

    /**
     * Ambil hanya data branding lab (untuk frontend).
     * Endpoint public — tidak perlu auth.
     *
     * @throws ApiException
     */
    public function getBranding(string $slug): array
    {
        $lab = $this->findBySlug($slug);

        return [
            'name'            => $lab->name,
            'primary_color'   => $lab->primary_color,
            'secondary_color' => $lab->secondary_color,
            'logo'            => $lab->logo,
            'hero_image'      => $lab->hero_image,
            'favicon'         => $lab->favicon,
        ];
    }
}