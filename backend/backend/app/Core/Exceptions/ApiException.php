<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

/**
 * API Exception.
 *
 * Digunakan di Service layer untuk melempar error yang
 * otomatis diconvert ke JSON response oleh Handler.
 */
class ApiException extends Exception
{
    public function __construct(
        string $message = 'An error occurred',
        private readonly int $statusCode = 400,
        private readonly mixed $errors = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }

    /**
     * Factory methods untuk error umum.
     */
    public static function notFound(string $resource = 'Resource'): self
    {
        return new self("{$resource} not found", 404);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403);
    }

    public static function unprocessable(string $message, mixed $errors = null): self
    {
        return new self($message, 422, $errors);
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }
}
