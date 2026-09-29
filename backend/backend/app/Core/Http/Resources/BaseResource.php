<?php

declare(strict_types=1);

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API Resource.
 *
 * Override withResponse untuk memastikan semua resource
 * menggunakan format response yang konsisten.
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Tambahkan wrapper 'data' dan metadata ke response.
     */
    public function withResponse(Request $request, $response): void
    {
        $data = $response->getData(true);

        $response->setData([
            'success' => true,
            'message' => 'Success',
            'data'    => $data['data'] ?? $data,
        ]);
    }
}
