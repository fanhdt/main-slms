<?php

declare(strict_types=1);

namespace App\Domain\LabService\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadServiceImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}