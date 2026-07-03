<?php

declare(strict_types=1);

namespace App\Domain\Asset\Requests;

use App\Domain\Asset\Enums\AssetCategory;
use App\Domain\Asset\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_id'         => ['required', 'integer', 'exists:labs,id'],
            'name'           => ['required', 'string', 'max:255'],
            'code'           => [
                'required',
                'string',
                'max:50',
                Rule::unique('assets', 'code')->whereNull('deleted_at'),
            ],
            'category'       => ['required', Rule::in(array_column(AssetCategory::cases(), 'value'))],
            'brand'          => ['nullable', 'string', 'max:100'],
            'model'          => ['nullable', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'serial_number'  => ['nullable', 'string', 'max:100'],
            'status'         => ['nullable', Rule::in(array_column(AssetStatus::cases(), 'value'))],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_date'  => ['nullable', 'date'],
            'specifications' => ['nullable', 'array'],
            'image'          => ['nullable', 'string'],
            'is_rentable'    => ['nullable', 'boolean'],
            'rental_price'   => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'lab_id.required'   => 'Lab wajib dipilih.',
            'lab_id.exists'     => 'Lab tidak ditemukan.',
            'name.required'     => 'Nama aset wajib diisi.',
            'code.required'     => 'Kode aset wajib diisi.',
            'code.unique'       => 'Kode aset sudah digunakan.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.in'       => 'Kategori tidak valid.',
            'status.in'         => 'Status tidak valid.',
        ];
    }
}