<?php
declare(strict_types=1);
namespace App\Domain\Portfolio\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotographerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:150'],
            'bio'       => ['sometimes', 'nullable', 'string', 'max:1000'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:100'],
            'order'     => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}