<?php

declare(strict_types=1);

namespace App\Domain\Photo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi role/permission ditangani di route middleware
    }

    public function rules(): array
    {
        return [
            'files'   => ['required', 'array', 'min:1', 'max:100'],
            'files.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:20480', // 20MB per file
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required'   => 'Minimal upload 1 foto.',
            'files.max'        => 'Maksimal 100 foto per upload.',
            'files.*.mimes'    => 'File harus berformat JPG, PNG, atau WEBP.',
            'files.*.max'      => 'Ukuran tiap foto maksimal 20MB.',
        ];
    }
}