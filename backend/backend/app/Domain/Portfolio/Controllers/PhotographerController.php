<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Portfolio\Requests\StorePhotographerRequest;
use App\Domain\Portfolio\Requests\UpdatePhotographerRequest;
use App\Domain\Portfolio\Resources\PhotographerResource;
use App\Domain\Portfolio\Services\PhotographerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotographerController extends ApiController
{
    public function __construct(
        private readonly PhotographerService $photographerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
         $photographers = $this->photographerService->listForLab(
        (int) $request->query('lab_id'),
        $request->boolean('active_only')
    );
        return $this->success(PhotographerResource::collection($photographers));
}

public function publicIndex(int $labId): JsonResponse
{
    $photographers = $this->photographerService->listForLab($labId, true);

    return $this->success(PhotographerResource::collection($photographers));
}

    public function store(StorePhotographerRequest $request): JsonResponse
    {
        $photographer = $this->photographerService->create(
            $request->validated(),
            $request->file('photo')
        );

        return $this->created(new PhotographerResource($photographer), 'Profil fotografer berhasil dibuat.');
    }

    public function update(UpdatePhotographerRequest $request, string $uuid): JsonResponse
    {
        $photographer = $this->photographerService->update($uuid, $request->validated());

        return $this->success(new PhotographerResource($photographer), 'Profil fotografer berhasil diupdate.');
    }

    public function updatePhoto(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photographer = $this->photographerService->updatePhoto($uuid, $request->file('photo'));

        return $this->success(new PhotographerResource($photographer), 'Foto profil berhasil diganti.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->photographerService->delete($uuid);

        return $this->successMessage('Fotografer berhasil dihapus.');
    }
}