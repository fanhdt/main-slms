<?php

declare(strict_types=1);

namespace App\Domain\Lab\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');

        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'     => ['sometimes', 'nullable', 'string'],
            'primary_color'   => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo'            => ['sometimes', 'nullable', 'string'],
            'hero_image'      => ['sometimes', 'nullable', 'string'],
            'favicon'         => ['sometimes', 'nullable', 'string'],
            'contact'         => ['sometimes', 'nullable', 'array'],
            'contact.email'   => ['nullable', 'email'],
            'contact.phone'   => ['nullable', 'string'],
            'contact.address' => ['nullable', 'string'],
            'settings'        => ['sometimes', 'nullable', 'array'],
            'is_active'       => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.regex'   => 'Primary color harus format hex (#RRGGBB).',
            'secondary_color.regex' => 'Secondary color harus format hex (#RRGGBB).',
        ];
    }
}