<?php

declare(strict_types=1);

namespace App\Domain\LabService\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\LabService\Resources\ServiceResource;
use App\Domain\LabService\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Domain\LabService\Enums\ServiceType;
use App\Domain\LabService\Enums\PricingType;

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
        $service = $this->serviceService->findByUuid($uuid);

        return $this->success(new ServiceResource($service));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lab_id'       => ['required', 'integer', 'exists:labs,id'],
            'name'         => ['required', 'string', 'max:255'],
            'type'         => ['required', Rule::in(array_column(ServiceType::cases(), 'value'))],
            'description'  => ['nullable', 'string'],
            'pricing_type' => ['required', Rule::in(array_column(PricingType::cases(), 'value'))],
            'price'        => ['required', 'numeric', 'min:0'],
            'duration'     => ['nullable', 'integer', 'min:1'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'includes'     => ['nullable', 'array'],
            'image'        => ['nullable', 'string'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $service = $this->serviceService->create($data);

        return $this->created(new ServiceResource($service), 'Service berhasil dibuat.');
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'type'         => ['sometimes', Rule::in(array_column(ServiceType::cases(), 'value'))],
            'description'  => ['sometimes', 'nullable', 'string'],
            'pricing_type' => ['sometimes', Rule::in(array_column(PricingType::cases(), 'value'))],
            'price'        => ['sometimes', 'numeric', 'min:0'],
            'duration'     => ['sometimes', 'nullable', 'integer', 'min:1'],
            'min_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'includes'     => ['sometimes', 'nullable', 'array'],
            'image'        => ['sometimes', 'nullable', 'string'],
            'is_active'    => ['sometimes', 'boolean'],
        ]);

        $service = $this->serviceService->update($uuid, $data);

        return $this->success(new ServiceResource($service), 'Service berhasil diupdate.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->serviceService->delete($uuid);

        return $this->successMessage('Service berhasil dihapus.');
    }
}