<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Portfolio\Models\Photographer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotographerService extends BaseService
{
    public function listForLab(int $labId, bool $activeOnly = false): Collection
    {
        $query = Photographer::query()->where('lab_id', $labId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('order')->get();
    }

    /** @throws ApiException */
    public function findByUuid(string $uuid): Photographer
    {
        $photographer = Photographer::findByUuid($uuid);

        if (! $photographer) {
            throw ApiException::notFound('Photographer');
        }

        return $photographer;
    }

    public function create(array $data, ?UploadedFile $photo = null): Photographer
    {
        $path = null;

        if ($photo) {
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
            $path = "photographers/{$data['lab_id']}/{$filename}";
            Storage::disk(Photographer::IMAGE_DISK)->put($path, file_get_contents($photo->getRealPath()));
        }

        return Photographer::create([
            'lab_id'    => $data['lab_id'],
            'name'      => $data['name'],
            'photo'     => $path,
            'bio'       => $data['bio'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'order'     => $data['order'] ?? 0,
        ]);
    }

    /** @throws ApiException */
    public function update(string $uuid, array $data): Photographer
    {
        $photographer = $this->findByUuid($uuid);
        $photographer->update($data);

        return $photographer->fresh();
    }

    /** @throws ApiException */
    public function updatePhoto(string $uuid, UploadedFile $file): Photographer
    {
        $photographer = $this->findByUuid($uuid);

        if ($photographer->photo) {
            Storage::disk(Photographer::IMAGE_DISK)->delete($photographer->photo);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "photographers/{$photographer->lab_id}/{$filename}";
        Storage::disk(Photographer::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

        $photographer->update(['photo' => $path]);

        return $photographer->fresh();
    }

    /** @throws ApiException */
    public function delete(string $uuid): void
    {
        $photographer = $this->findByUuid($uuid);

        if ($photographer->photo) {
            Storage::disk(Photographer::IMAGE_DISK)->delete($photographer->photo);
        }

        $photographer->delete(); // portfolio miliknya photographer_id ikut null (nullOnDelete)
    }
}