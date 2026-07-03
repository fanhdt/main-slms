<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base API Controller.
 *
 * Semua controller di SLMS extends class ini.
 * Menyediakan helper untuk response yang konsisten.
 */
abstract class ApiController extends Controller
{
     use AuthorizesRequests;
    /**
     * Response sukses dengan data.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Response sukses tanpa data (created, deleted, dll).
     */
    protected function successMessage(
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => null,
        ], $status);
    }

    /**
     * Response created (201).
     */
    protected function created(
        mixed $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Response error.
     */
    protected function error(
        string $message = 'Error',
        int $status = 400,
        mixed $errors = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Response 404.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Response 403.
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Response no content (204) — untuk delete.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
