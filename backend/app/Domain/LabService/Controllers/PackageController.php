<?php

declare(strict_types=1);

namespace App\Domain\LabService\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\LabService\Resources\PackageResource;
use App\Domain\LabService\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends ApiController
{
    public function __construct(
        private readonly PackageService $packageService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $packages = $this->packageService->paginate($request->all());

        return $this->success(
            PackageResource::collection($packages)->response()->getData(true)
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $package = $this->packageService->findByUuid($uuid);

        return $this->success(new PackageResource($package));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lab_id'      => ['required', 'integer', 'exists:labs,id'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'discount'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'duration'    => ['nullable', 'integer', 'min:1'],
            'includes'    => ['nullable', 'array'],
            'addons'      => ['nullable', 'array'],
            'image'       => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
            'is_custom'   => ['nullable', 'boolean'],
            
            // Validasi untuk items (relasi ke service)
            'items'       => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'items.*.quantity'   => ['nullable', 'integer', 'min:1'],
            'items.*.notes'      => ['nullable', 'string'],
        ]);

        $package = $this->packageService->create($data);

        return $this->created(new PackageResource($package), 'Package berhasil dibuat.');
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'discount'    => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'duration'    => ['sometimes', 'nullable', 'integer', 'min:1'],
            'includes'    => ['sometimes', 'nullable', 'array'],
            'addons'      => ['sometimes', 'nullable', 'array'],
            'image'       => ['sometimes', 'nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'is_custom'   => ['sometimes', 'boolean'],
            
            // Validasi update items (replace all)
            'items'       => ['sometimes', 'array', 'min:1'],
            'items.*.service_id' => ['required_with:items', 'integer', 'exists:services,id'],
            'items.*.quantity'   => ['sometimes', 'nullable', 'integer', 'min:1'],
            'items.*.notes'      => ['sometimes', 'nullable', 'string'],
        ]);

        $package = $this->packageService->update($uuid, $data);

        return $this->success(new PackageResource($package), 'Package berhasil diupdate.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->packageService->delete($uuid);

        return $this->successMessage('Package berhasil dihapus.');
    }
}