<?php

declare(strict_types=1);

namespace App\Domain\User\Requests;

use App\Domain\User\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'password'  => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role'      => ['sometimes', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'        => 'Nama harus berupa teks.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.in'            => 'Role tidak valid.',
        ];
    }
}