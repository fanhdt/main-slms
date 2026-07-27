<?php

declare(strict_types=1);

namespace App\Domain\LabService\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\LabService\Resources\ServiceResource;
use App\Domain\LabService\Resources\ServiceOptionResource;
use App\Domain\LabService\Services\ServiceService;
use App\Domain\LabService\Requests\UploadServiceImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Domain\LabService\Enums\ServiceType;
use App\Domain\LabService\Enums\PricingType;
use App\Domain\LabService\Enums\ServiceOptionPriceType;

class ServiceController extends ApiController
{
    public function __construct(
        private readonly ServiceService $serviceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $services = $this->serviceService->paginate($request->all());

        return $this->success(
            ServiceResource::collection($services)->response()->getData(true)
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $service = $this->serviceService->findByUuid($uuid, withOptions: true);

        return $this->success(new ServiceResource($service));
    }

    // store()
public function store(Request $request): JsonResponse
{
    $type = $request->input('type');
    $isCustomPricing = $request->boolean('is_custom_pricing');
    // Editing Dasar: photo_editing TANPA custom -> harga murni dari daftar pilihan (ServiceOption)
    $isOptionOnly = $type === \App\Domain\LabService\Enums\ServiceType::PhotoEditing->value
        && !$isCustomPricing;

    $data = $request->validate([
        'lab_id'             => ['required', 'integer', 'exists:labs,id'],
        'name'                => ['required', 'string', 'max:255'],
        'type'                => ['required', Rule::in(array_column(ServiceType::cases(), 'value'))],
        'description'         => ['nullable', 'string'],
        'pricing_type'        => [$isOptionOnly ? 'nullable' : 'required', Rule::in(array_column(PricingType::cases(), 'value'))],
        'price'               => [$isOptionOnly ? 'nullable' : 'required', 'numeric', 'min:0'],
        'duration'            => ['nullable', 'integer', 'min:1'],
        'min_quantity'        => ['nullable', 'integer', 'min:1'],
        'max_quantity'        => ['nullable', 'integer', 'min:1'],
        'includes'            => ['nullable', 'array'],
        'image'               => ['nullable', 'string'],
        'is_active'           => ['nullable', 'boolean'],
        'is_custom_pricing'   => ['nullable', 'boolean'],
    ]);

    if ($isOptionOnly) {
        $data['price'] = null;
        $data['pricing_type'] = null;
    }
    $data['is_custom_pricing'] = $isCustomPricing;

    $service = $this->serviceService->create($data);

    return $this->created(new ServiceResource($service), 'Service berhasil dibuat.');
}

public function update(Request $request, string $uuid): JsonResponse
{
    $isCustomPricing = $request->has('is_custom_pricing')
        ? $request->boolean('is_custom_pricing')
        : null;

    $data = $request->validate([
        'name'                => ['sometimes', 'string', 'max:255'],
        'type'                => ['sometimes', Rule::in(array_column(ServiceType::cases(), 'value'))],
        'description'         => ['sometimes', 'nullable', 'string'],
        'pricing_type'        => ['sometimes', 'nullable', Rule::in(array_column(PricingType::cases(), 'value'))],
        'price'               => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'duration'            => ['sometimes', 'nullable', 'integer', 'min:1'],
        'min_quantity'        => ['sometimes', 'nullable', 'integer', 'min:1'],
        'max_quantity'        => ['sometimes', 'nullable', 'integer', 'min:1'],
        'includes'            => ['sometimes', 'nullable', 'array'],
        'image'               => ['sometimes', 'nullable', 'string'],
        'is_active'           => ['sometimes', 'boolean'],
        'is_custom_pricing'   => ['sometimes', 'boolean'],
    ]);

    $existingService = $this->serviceService->findByUuid($uuid);
    $effectiveType = $data['type'] ?? $existingService->type->value;
    $effectiveIsCustom = $isCustomPricing ?? $existingService->is_custom_pricing;
    $willBeOptionOnly = $effectiveType === \App\Domain\LabService\Enums\ServiceType::PhotoEditing->value
        && !$effectiveIsCustom;

    if ($willBeOptionOnly) {
        $data['price'] = null;
        $data['pricing_type'] = null;
    }

    $service = $this->serviceService->update($uuid, $data);

    return $this->success(new ServiceResource($service), 'Service berhasil diupdate.');
}

    public function updateImage(UploadServiceImageRequest $request, string $uuid): JsonResponse
    {
        $service = $this->serviceService->updateImage($uuid, $request->file('image'));

        return $this->success(new ServiceResource($service), 'Gambar berhasil diupdate.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->serviceService->delete($uuid);

        return $this->successMessage('Service berhasil dihapus.');
    }

    // ============================================================
    // Service Options — CRUD nested di bawah Service
    // ============================================================

    public function listOptions(string $uuid): JsonResponse
    {
        $options = $this->serviceService->listOptions($uuid);

        return $this->success(ServiceOptionResource::collection($options));
    }

    public function storeOption(Request $request, string $uuid): JsonResponse
{
    $isCustom = $request->input('price_type') === \App\Domain\LabService\Enums\ServiceOptionPriceType::Custom->value;

    $data = $request->validate([
        'name'          => ['required', 'string', 'max:255'],
        'description'   => [$isCustom ? 'required' : 'nullable', 'string'],
        'price_type'    => ['required', Rule::in(array_column(ServiceOptionPriceType::cases(), 'value'))],
        'price'         => [$isCustom ? 'nullable' : 'required', 'numeric', 'min:0'],
        'extra_minutes' => ['nullable', 'integer', 'min:0'],
        'order'         => ['nullable', 'integer', 'min:0'],
        'is_active'     => ['nullable', 'boolean'],
    ]);

    if ($isCustom) {
        $data['price'] = 0; // kolom tetap perlu angka default kalau belum nullable-migrated; aman diisi 0
    }

    $option = $this->serviceService->createOption($uuid, $data);

    return $this->created(new ServiceOptionResource($option), 'Opsi layanan berhasil ditambahkan.');
}

    public function updateOption(Request $request, string $uuid, string $optionUuid): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'price_type'    => ['sometimes', Rule::in(array_column(ServiceOptionPriceType::cases(), 'value'))],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'extra_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'order'         => ['sometimes', 'integer', 'min:0'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $option = $this->serviceService->updateOption($uuid, $optionUuid, $data);

        return $this->success(new ServiceOptionResource($option), 'Opsi layanan berhasil diupdate.');
    }

    public function destroyOption(string $uuid, string $optionUuid): JsonResponse
    {
        $this->serviceService->deleteOption($uuid, $optionUuid);

        return $this->successMessage('Opsi layanan berhasil dihapus.');
    }
}