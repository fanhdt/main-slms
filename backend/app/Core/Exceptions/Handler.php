<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\ApiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Global Exception Handler.
 *
 * Mengubah semua exception menjadi JSON response yang konsisten
 * untuk semua request ke /api/*.
 */
class Handler extends ExceptionHandler
{
    /**
     * Exception yang tidak perlu di-report ke log.
     */
    protected $dontReport = [
        ApiException::class,
    ];

    /**
     * Exception yang tidak perlu di-flash ke session.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render exception ke HTTP response.
     * Untuk request API, selalu return JSON.
     */
    public function render($request, Throwable $e): mixed
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Custom API Exception dari Service layer
        if ($e instanceof ApiException) {
            $payload = [
                'success' => false,
                'message' => $e->getMessage(),
            ];

            if ($e->getErrors() !== null) {
                $payload['errors'] = $e->getErrors();
            }

            return response()->json($payload, $e->getStatusCode());
        }

        // Laravel Validation Exception
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Unauthenticated
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Spatie Permission Unauthorized
        if ($e instanceof UnauthorizedException) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have the required permission.',
            ], 403);
        }

        // 404 Not Found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
            ], 404);
        }

        // Generic HTTP Exception
        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'HTTP Error',
            ], $e->getStatusCode());
        }

        // Unexpected error — jangan expose detail di production
        $message = config('app.debug')
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}
