<?php
declare(strict_types=1);
namespace App\Domain\Portfolio\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotographerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lab_id'    => ['required', 'integer', 'exists:labs,id'],
            'name'      => ['required', 'string', 'max:150'],
            'bio'       => ['nullable', 'string', 'max:1000'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'order'     => ['nullable', 'integer', 'min:0'],
            'photo'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}