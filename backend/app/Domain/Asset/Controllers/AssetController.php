<?php

declare(strict_types=1);

namespace App\Domain\Asset\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Asset\DTOs\CreateAssetDTO;
use App\Domain\Asset\DTOs\UpdateAssetDTO;
use App\Domain\Asset\Enums\AssetStatus;
use App\Domain\Asset\Requests\CreateAssetRequest;
use App\Domain\Asset\Requests\UpdateAssetRequest;
use App\Domain\Asset\Resources\AssetResource;
use App\Domain\Asset\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends ApiController
{
    public function __construct(
        private readonly AssetService $assetService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $assets = $this->assetService->paginate($request->all());

        return $this->success(
            AssetResource::collection($assets)->response()->getData(true)
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $asset = $this->assetService->findByUuid($uuid);

        return $this->success(new AssetResource($asset));
    }

    public function store(CreateAssetRequest $request): JsonResponse
    {
        $asset = $this->assetService->create(
            CreateAssetDTO::fromRequest($request->validated())
        );

        return $this->created(new AssetResource($asset), 'Aset berhasil dibuat.');
    }

    public function update(UpdateAssetRequest $request, string $uuid): JsonResponse
    {
        $asset = $this->assetService->update(
            $uuid,
            UpdateAssetDTO::fromRequest($request->validated())
        );

        return $this->success(new AssetResource($asset), 'Aset berhasil diupdate.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->assetService->delete($uuid);

        return $this->successMessage('Aset berhasil dihapus.');
    }

    /**
     * Update status aset saja — endpoint khusus untuk operator
     * yang perlu cepat ubah status tanpa edit full form.
     */
    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(array_column(AssetStatus::cases(), 'value'))],
        ]);

        $asset = $this->assetService->updateStatus($uuid, $request->status);

        return $this->success(new AssetResource($asset), 'Status aset berhasil diupdate.');
    }

    public function updateImage(Request $request, string $uuid): JsonResponse
{
    $request->validate([
        'image' => ['required', 'image', 'max:5120'],
    ]);

    $asset = $this->assetService->updateImage($uuid, $request->file('image'));

    return $this->success(new AssetResource($asset), 'Gambar aset berhasil diupdate.');
}

public function checkAvailability(Request $request): JsonResponse
{
    $request->validate([
        'asset_uuids'   => ['required', 'array', 'min:1'],
        'asset_uuids.*' => ['required', 'string', 'exists:assets,uuid'],
        'start_date'    => ['required', 'date'],
        'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
    ]);

    $result = $this->assetService->checkAvailability(
        $request->input('asset_uuids'),
        $request->input('start_date'),
        $request->input('end_date'),
    );

    return $this->success($result);
}
}