<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Portfolio\Models\PhotographerPortfolio;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioService extends BaseService
{
    /**
     * List nama-nama fotografer unik di sebuah lab (untuk tab/dropdown di landing page).
     */
    // public function photographersOf(int $labId): array
    // {
    //     return PhotographerPortfolio::query()
    //         ->where('lab_id', $labId)
    //         ->distinct()
    //         ->orderBy('photographer_name')
    //         ->pluck('photographer_name')
    //         ->all();
    // }



    /**
     * Galeri milik SATU fotografer, dengan pagination sendiri —
     * bukan feed gabungan semua fotografer.
     */
   public function paginateForPhotographer(int $labId, string $photographerUuid, int $perPage = 12): LengthAwarePaginator
{
    $photographer = \App\Domain\Portfolio\Models\Photographer::findByUuid($photographerUuid);

    return PhotographerPortfolio::query()
        ->with('photographer')
        ->where('lab_id', $labId)
        ->where('photographer_id', $photographer?->id)
        ->orderBy('order')
        ->latest()
        ->paginate($perPage);
}

    /**
     * Untuk admin panel: semua portofolio lab (bisa difilter per fotografer juga).
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = PhotographerPortfolio::query()->with('lab');

        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        if (isset($filters['photographer_name'])) {
    $query->whereHas('photographer', function ($q) use ($filters) {
        $q->where('name', 'like', '%' . $filters['photographer_name'] . '%');
    });
}

        return $query->orderBy('order')->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @throws ApiException
     */
    public function findByUuid(string $uuid): PhotographerPortfolio
    {
        $portfolio = PhotographerPortfolio::findByUuid($uuid);

        if (! $portfolio) {
            throw ApiException::notFound('Portfolio');
        }

        return $portfolio;
    }

    public function create(array $data, UploadedFile $file): PhotographerPortfolio
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "portfolios/{$data['lab_id']}/{$filename}";

        Storage::disk(PhotographerPortfolio::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

        return PhotographerPortfolio::create([
            'lab_id'            => $data['lab_id'],
           'photographer_id'   => $data['photographer_id'],
            'image'             => $path,
            'caption'           => $data['caption'] ?? null,
            'order'             => $data['order'] ?? 0,
        ]);
    }

    /**
     * @throws ApiException
     */
    public function update(string $uuid, array $data): PhotographerPortfolio
    {
        $portfolio = $this->findByUuid($uuid);

        if (! empty($data)) {
            $portfolio->update($data);
        }

        return $portfolio->fresh();
    }

    /**
     * @throws ApiException
     */
    public function updateImage(string $uuid, UploadedFile $file): PhotographerPortfolio
    {
        $portfolio = $this->findByUuid($uuid);

        if ($portfolio->image) {
            Storage::disk(PhotographerPortfolio::IMAGE_DISK)->delete($portfolio->image);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "portfolios/{$portfolio->lab_id}/{$filename}";

        Storage::disk(PhotographerPortfolio::IMAGE_DISK)->put($path, file_get_contents($file->getRealPath()));

        $portfolio->update(['image' => $path]);

        return $portfolio->fresh();
    }

    /**
     * @throws ApiException
     */
    public function delete(string $uuid): void
    {
        $portfolio = $this->findByUuid($uuid);

        if ($portfolio->image) {
            Storage::disk(PhotographerPortfolio::IMAGE_DISK)->delete($portfolio->image);
        }

        $portfolio->delete();
    }
}