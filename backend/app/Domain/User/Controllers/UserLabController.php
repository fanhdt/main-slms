<?php

declare(strict_types=1);

namespace App\Domain\User\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\User\Services\UserLabService;
use App\Domain\Lab\Resources\LabResource;
use App\Domain\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLabController extends ApiController
{
    public function __construct(
        private readonly UserLabService $userLabService,
    ) {}

    /**
     * Ambil semua lab yang user ini punya akses.
     */
    public function getUserLabs(string $userUuid): JsonResponse
    {
        $user = $this->userLabService->getUserLabs($userUuid);

        return $this->success(
            LabResource::collection($user->labs)->response()->getData(true)
        );
    }

    /**
     * Ambil semua user yang punya akses ke lab tertentu.
     */
    public function getLabUsers(string $labUuid): JsonResponse
    {
        $lab = $this->userLabService->getLabUsers($labUuid);

        return $this->success(
            UserResource::collection($lab->users)->response()->getData(true)
        );
    }

    /**
     * Assign user ke lab.
     */
    public function assign(Request $request, string $userUuid): JsonResponse
    {
        $data = $request->validate([
            'lab_uuid' => ['required', 'string', 'exists:labs,uuid'],
            'role'     => ['nullable', 'string'],
        ]);

        $user = $this->userLabService->assign(
            $userUuid,
            $data['lab_uuid'],
            $data['role'] ?? null
        );

        return $this->success(
            new UserResource($user),
            'User berhasil diassign ke lab.'
        );
    }

    /**
     * Cabut akses user dari lab.
     */
    public function revoke(Request $request, string $userUuid): JsonResponse
    {
        $data = $request->validate([
            'lab_uuid' => ['required', 'string', 'exists:labs,uuid'],
        ]);

        $this->userLabService->revoke($userUuid, $data['lab_uuid']);

        return $this->successMessage('Akses user ke lab berhasil dicabut.');
    }
}