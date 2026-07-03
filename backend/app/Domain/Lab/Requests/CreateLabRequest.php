<?php

declare(strict_types=1);

namespace App\Domain\Lab\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'slug'            => ['required', 'string', 'max:100', 'unique:labs,slug', 'regex:/^[a-z0-9-]+$/'],
            'description'     => ['nullable', 'string'],
            'primary_color'   => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo'            => ['nullable', 'string'],
            'hero_image'      => ['nullable', 'string'],
            'favicon'         => ['nullable', 'string'],
            'contact'         => ['nullable', 'array'],
            'contact.email'   => ['nullable', 'email'],
            'contact.phone'   => ['nullable', 'string'],
            'contact.address' => ['nullable', 'string'],
            'settings'        => ['nullable', 'array'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Nama lab wajib diisi.',
            'slug.required'         => 'Slug wajib diisi.',
            'slug.unique'           => 'Slug sudah digunakan lab lain.',
            'slug.regex'            => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung.',
            'primary_color.regex'   => 'Primary color harus format hex (#RRGGBB).',
            'secondary_color.regex' => 'Secondary color harus format hex (#RRGGBB).',
        ];
    }
}