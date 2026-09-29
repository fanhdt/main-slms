<?php

declare(strict_types=1);

namespace App\Domain\Asset\Requests;

use App\Domain\Asset\Enums\AssetCategory;
use App\Domain\Asset\Enums\AssetStatus;
use App\Domain\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');
        $asset = Asset::where('uuid', $uuid)->first();

        return [
            'name'           => ['sometimes', 'string', 'max:255'],
            'category'       => ['sometimes', Rule::in(array_column(AssetCategory::cases(), 'value'))],
            'brand'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'model'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'description'    => ['sometimes', 'nullable', 'string'],
            'serial_number'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'status'         => ['sometimes', Rule::in(array_column(AssetStatus::cases(), 'value'))],
            'purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'purchase_date'  => ['sometimes', 'nullable', 'date'],
            'specifications' => ['sometimes', 'nullable', 'array'],
            'image'          => ['sometimes', 'nullable', 'string'],
            'is_rentable'    => ['sometimes', 'boolean'],
            'rental_price'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'Kategori tidak valid.',
            'status.in'   => 'Status tidak valid.',
        ];
    }
}