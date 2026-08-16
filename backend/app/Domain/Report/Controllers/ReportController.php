<?php

declare(strict_types=1);

namespace App\Domain\Report\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Report\Models\Report;
use App\Domain\Report\Resources\ReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::query()->where('lab_id', $request->query('lab_id'));

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $reports = $query->latest('period_start')->paginate($request->integer('per_page', 15));

        return $this->success(ReportResource::collection($reports)->response()->getData(true));
    }

    public function show(string $uuid): JsonResponse
    {
        $report = Report::where('uuid', $uuid)->firstOrFail();

        return $this->success(new ReportResource($report));
    }
}