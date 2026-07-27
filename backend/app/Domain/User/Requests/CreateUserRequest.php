<?php

declare(strict_types=1);

namespace App\Domain\User\Requests;

use App\Domain\User\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'phone'     => ['nullable', 'string', 'max:20'],
            'role'      => ['required', Rule::in(UserRole::assignableRoles())],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Nama wajib diisi.',
            'email.required'          => 'Email wajib diisi.',
            'email.unique'            => 'Email sudah terdaftar.',
            'password.required'       => 'Password wajib diisi.',
            'password.confirmed'      => 'Konfirmasi password tidak cocok.',
            'password.min'            => 'Password minimal 8 karakter ',
            'password.mixed'          => 'Password harus mengandung kombinasi huruf besar dan huruf kecil',
            'password.letters'        => 'Password harus mengandung minimal satu huruf',
            'password.numbers'        => 'Password harus mengandung minimal satu angka',
            'password.symbols'        => 'Password harus mengandung minimal satu simbol (contoh: ! @ # $ %)',
            'password.uncompromised'  => 'Password ini pernah muncul dalam kebocoran data. Gunakan password lain yang lebih aman.',
            'role.required'           => 'Role wajib dipilih.',
            'role.in'                 => 'Role tidak valid atau tidak boleh diberikan lewat aplikasi.',
        ];
    }
}