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
            'role'      => ['sometimes', Rule::in(UserRole::assignableRoles())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'             => 'Nama harus berupa teks.',
            'password.confirmed'     => 'Konfirmasi password tidak cocok.',
            'password.min'            => 'Password minimal 8 karakter ',
            'password.mixed'          => 'Password harus mengandung kombinasi huruf besar dan huruf kecil',
            'password.letters'        => 'Password harus mengandung minimal satu huruf',
            'password.numbers'        => 'Password harus mengandung minimal satu angka',
            'password.symbols'        => 'Password harus mengandung minimal satu simbol (contoh: ! @ # $ %)',
            'password.uncompromised'  => 'Password ini pernah muncul dalam kebocoran data. Gunakan password lain yang lebih aman.',
            'role.in'                 => 'Role tidak valid atau tidak boleh diberikan lewat aplikasi.',
        ];
    }
}