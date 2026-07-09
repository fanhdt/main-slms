<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Portfolio\Requests\StorePortfolioRequest;
use App\Domain\Portfolio\Requests\UpdatePortfolioRequest;
use App\Domain\Portfolio\Resources\PortfolioResource;
use App\Domain\Portfolio\Services\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends ApiController
{
    public function __construct(
        private readonly PortfolioService $portfolioService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $portfolios = $this->portfolioService->paginate($request->all());

        return $this->success(
            PortfolioResource::collection($portfolios)->response()->getData(true)
        );
    }

    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $portfolio = $this->portfolioService->create(
            $request->validated(),
            $request->file('image')
        );

        return $this->created(new PortfolioResource($portfolio), 'Foto portofolio berhasil ditambahkan.');
    }

    public function update(UpdatePortfolioRequest $request, string $uuid): JsonResponse
    {
        $portfolio = $this->portfolioService->update($uuid, $request->validated());

        return $this->success(new PortfolioResource($portfolio), 'Portofolio berhasil diupdate.');
    }

    public function updateImage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $portfolio = $this->portfolioService->updateImage($uuid, $request->file('image'));

        return $this->success(new PortfolioResource($portfolio), 'Foto berhasil diganti.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->portfolioService->delete($uuid);

        return $this->successMessage('Foto portofolio berhasil dihapus.');
    }

    // ---- Public (untuk landing page lab fotografi) ----
    // method photographers() DIHAPUS — pindah ke PhotographerController::index (route publik)

    public function galleryByPhotographer(Request $request, int $labId, string $photographerUuid): JsonResponse
    {
        $portfolios = $this->portfolioService->paginateForPhotographer(
            $labId,
            $photographerUuid,
            (int) $request->input('per_page', 12)
        );

        return $this->success(
            PortfolioResource::collection($portfolios)->response()->getData(true)
        );
    }
}