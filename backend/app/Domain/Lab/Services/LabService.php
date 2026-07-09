<?php

declare(strict_types=1);

namespace App\Domain\Lab\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Lab\DTOs\CreateLabDTO;
use App\Domain\Lab\DTOs\UpdateLabDTO;
use App\Domain\Lab\Models\Lab;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class LabService extends BaseService
{
    private const IMAGE_TYPES = ['logo', 'hero_image', 'favicon'];

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Lab::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
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

    public function updateRentalRates(string $uuid, array $rates): Lab
    {
        $lab = $this->findByUuid($uuid);

        $settings = $lab->settings ?? [];
        $settings['lab_rental'] = [
            'student_price_per_hour' => (float) $rates['student_price_per_hour'],
            'public_price_per_hour'  => (float) $rates['public_price_per_hour'],
        ];

        $lab->update(['settings' => $settings]);

        return $lab->fresh();
    }

    /**
     * Upload/ganti gambar branding lab (logo, hero_image, atau favicon) ke MinIO/S3.
     * File lama otomatis dihapus dari disk kalau ada penggantian.
     *
     * @throws ApiException
     */
    public function updateImage(string $uuid, string $type, UploadedFile $file): Lab
    {
        $lab = $this->findByUuid($uuid);

        $oldPath = $lab->{$type};
        if ($oldPath) {
            Storage::disk(Lab::IMAGE_DISK)->delete($oldPath);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "labs/{$lab->uuid}/{$type}/{$filename}";

        Storage::disk(Lab::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

        $lab->update([$type => $path]);

        return $lab->fresh();
    }

    /**
     * @throws ApiException
     */
    public function delete(string $uuid): void
    {
        $lab = $this->findByUuid($uuid);
        $lab->delete();
    }

    /**
     * @throws ApiException
     */
    public function getBranding(string $slug): array
    {
        $lab = $this->findBySlug($slug);

        return [
            'name'            => $lab->name,
            'primary_color'   => $lab->primary_color,
            'secondary_color' => $lab->secondary_color,
            'logo'            => $lab->logo_url,
            'hero_image'      => $lab->hero_image_url,
            'favicon'         => $lab->favicon_url,
        ];
    }
}