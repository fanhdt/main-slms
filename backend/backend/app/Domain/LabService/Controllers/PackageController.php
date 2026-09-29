<?php

declare(strict_types=1);

namespace App\Domain\LabService\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\LabService\Resources\PackageResource;
use App\Domain\LabService\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $data = $this->validateWithItems($request);

        $package = $this->packageService->create($data);

        return $this->created(new PackageResource($package), 'Package berhasil dibuat.');
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $data = $this->validateWithItems($request, isUpdate: true);

        $package = $this->packageService->update($uuid, $data);

        return $this->success(new PackageResource($package), 'Package berhasil diupdate.');
    }

    public function updateImage(Request $request, string $uuid): JsonResponse
{
    $request->validate([
        'image' => ['required', 'image', 'max:5120'],
    ]);

    $package = $this->packageService->updateImage($uuid, $request->file('image'));

    return $this->success(new PackageResource($package), 'Gambar package berhasil diupdate.');
}

    public function destroy(string $uuid): JsonResponse
    {
        $this->packageService->delete($uuid);

        return $this->successMessage('Package berhasil dihapus.');
    }

    private function validateWithItems(Request $request, bool $isUpdate = false): array
    {
        $itemsRule = $isUpdate ? 'sometimes|array|min:1' : 'required|array|min:1';

        $validator = Validator::make($request->all(), [
            'lab_id'      => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:labs,id'],
            'name'        => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price'       => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'discount'    => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'duration'    => ['sometimes', 'nullable', 'integer', 'min:1'],
            'includes'    => ['sometimes', 'nullable', 'array'],
            'addons'      => ['sometimes', 'nullable', 'array'],
            'image'       => ['sometimes', 'nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'is_custom'   => ['sometimes', 'boolean'],

            'items'                     => $itemsRule,
            'items.*.service_id'        => ['nullable', 'integer', 'exists:services,id'],
            'items.*.asset_id'          => ['nullable', 'integer', 'exists:assets,id'],
            'items.*.quantity'          => ['nullable', 'integer', 'min:1'],
            'items.*.duration_minutes'  => ['nullable', 'integer', 'min:1'],
            'items.*.notes'             => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('items', []) as $index => $item) {
                $hasService = !empty($item['service_id']);
                $hasAsset = !empty($item['asset_id']);

                if ($hasService === $hasAsset) {
                    // dua-duanya kosong ATAU dua-duanya terisi — sama-sama invalid
                    $validator->errors()->add(
                        "items.{$index}",
                        'Tiap item harus pilih salah satu: Jasa atau Alat (tidak boleh keduanya/kosong).'
                    );
                }
            }
        });

        return $validator->validate();
    }
}