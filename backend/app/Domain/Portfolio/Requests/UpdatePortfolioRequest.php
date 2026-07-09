<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photographer_id' => ['sometimes', 'integer', 'exists:photographers,id'],
            // 'photographer_name' => ['sometimes', 'string', 'max:150'],
            'caption'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'order'             => ['sometimes', 'integer', 'min:0'],
            // Ganti foto lewat endpoint terpisah (updateImage), konsisten dgn pola Lab/Service
        ];
    }
}