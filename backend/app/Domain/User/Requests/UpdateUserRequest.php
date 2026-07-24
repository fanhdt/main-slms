<?php

declare(strict_types=1);

namespace App\Domain\User\Requests;

use App\Domain\User\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'password'  => [
                'sometimes',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            // Hanya role yang boleh diassign lewat aplikasi — super_admin dikecualikan
            'role'      => ['sometimes', Rule::in(UserRole::assignableRoles())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'        => 'Nama harus berupa teks.',
            'password.min'       => 'Password minimal 8 karakter, kombinasi huruf besar/kecil, angka, dan simbol.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.uncompromised' => 'Password ini pernah bocor di kebocoran data lain. Gunakan password lain.',
            'role.in'            => 'Role tidak valid atau tidak boleh diberikan lewat aplikasi.',
        ];
    }
}