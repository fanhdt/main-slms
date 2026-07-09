<?php

declare(strict_types=1);

namespace App\Domain\Portfolio\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // digerbang lewat middleware `can:portfolios.create`
    }

    public function rules(): array
    {
        return [
            'lab_id'            => ['required', 'integer', 'exists:labs,id'],
            // 'photographer_name' => ['required', 'string', 'max:150'],
            'photographer_id'   => ['required', 'integer', 'exists:photographers,id'],
            'image'             => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'caption'           => ['nullable', 'string', 'max:255'],
            'order'             => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'lab_id.required'            => 'Lab wajib dipilih.',
            'lab_id.exists'              => 'Lab tidak ditemukan.',
            'photographer_name.required' => 'Nama fotografer wajib diisi.',
            'image.required'             => 'Foto wajib diupload.',
            'image.mimes'                => 'Format foto harus jpg, jpeg, png, atau webp.',
        ];
    }
}