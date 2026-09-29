<?php

declare(strict_types=1);

namespace App\Domain\Lab\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Lab\DTOs\CreateLabDTO;
use App\Domain\Lab\DTOs\UpdateLabDTO;
use App\Domain\Lab\Requests\CreateLabRequest;
use App\Domain\Lab\Requests\UpdateLabRequest;
use App\Domain\Lab\Requests\UploadLabImageRequest;
use App\Domain\Lab\Resources\LabResource;
use App\Domain\Lab\Services\LabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Core\Exceptions\ApiException;

class LabController extends ApiController
{
    public function __construct(
        private readonly LabService $labService,
    ) {}

    /**
     * Ambil semua lab (public).
     */
    public function index(Request $request): JsonResponse
    {
        $labs = $this->labService->paginate($request->all());

        return $this->success(
            LabResource::collection($labs)->response()->getData(true)
        );
    }

    /**
     * Ambil satu lab by slug (public).
     */
    public function show(string $slug): JsonResponse
    {
        $lab = $this->labService->findBySlug($slug);

        return $this->success(new LabResource($lab));
    }

    /**
     * Ambil branding lab by slug (public, untuk frontend).
     */
    public function branding(string $slug): JsonResponse
    {
        $branding = $this->labService->getBranding($slug);

        return $this->success($branding);
    }

    /**
     * Buat lab baru (admin only).
     */
    public function store(CreateLabRequest $request): JsonResponse
    {
        $lab = $this->labService->create(
            CreateLabDTO::fromRequest($request->validated())
        );

        return $this->created(new LabResource($lab), 'Lab berhasil dibuat.');
    }

    /**
     * Update lab (admin only).
     */
    public function update(UpdateLabRequest $request, string $uuid): JsonResponse
    {
        $lab = $this->labService->update(
            $uuid,
            UpdateLabDTO::fromRequest($request->validated())
        );

        return $this->success(new LabResource($lab), 'Lab berhasil diupdate.');
    }

    /**
     * Update branding lab (admin only).
     */
    public function updateBranding(UpdateLabRequest $request, string $uuid): JsonResponse
    {
        $lab = $this->labService->update(
            $uuid,
            UpdateLabDTO::fromRequest($request->validated())
        );

        return $this->success(new LabResource($lab), 'Branding berhasil diupdate.');
    }

    public function updateRentalRates(Request $request, string $uuid): JsonResponse
{
    $lab = $this->labService->findByUuid($uuid);

    if (!$request->user()->hasRole('super_admin') && !$request->user()->hasLabAccess($lab->id)) {
        throw ApiException::forbidden('Kamu tidak punya akses ke lab ini.');
    }

    $validated = $request->validate([
        'student_price_per_hour' => ['required', 'numeric', 'min:0'],
        'public_price_per_hour'  => ['required', 'numeric', 'min:0'],
    ]);

    $lab = $this->labService->updateRentalRates($uuid, $validated);

    return $this->success(new LabResource($lab), 'Harga sewa lab berhasil diupdate.');
}

public function updateImage(UploadLabImageRequest $request, string $uuid): JsonResponse
    {
        $lab = $this->labService->updateImage(
            $uuid,
            $request->validated('type'),
            $request->file('image')
        );

        return $this->success(new LabResource($lab), 'Gambar berhasil diupdate.');
    }

    /**
     * Hapus lab (super admin only).
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->labService->delete($uuid);

        return $this->successMessage('Lab berhasil dihapus.');
    }
}