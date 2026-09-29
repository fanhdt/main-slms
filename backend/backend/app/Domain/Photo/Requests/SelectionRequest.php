<?php

declare(strict_types=1);

namespace App\Domain\Photo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo_file_uuids'   => ['required', 'array', 'min:1'],
            'photo_file_uuids.*' => ['required', 'string', 'exists:photo_files,uuid'],
            'customer_note'      => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo_file_uuids.required'   => 'Pilih minimal 1 foto.',
            'photo_file_uuids.*.exists'   => 'Salah satu foto yang dipilih tidak valid.',
        ];
    }
}