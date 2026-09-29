<?php

declare(strict_types=1);

namespace App\Domain\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email'],
            'password'              => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'         => 'Token reset password tidak valid.',
            'email.required'         => 'Email wajib diisi.',
            'password.required'      => 'Password baru wajib diisi.',
            'password.confirmed'     => 'Konfirmasi password tidak cocok.',
            'password.min'           => 'Password minimal 8 karakter.',
            'password.mixed'         => 'Password harus mengandung kombinasi huruf besar dan huruf kecil',
            'password.letters'       => 'Password harus mengandung minimal satu huruf',
            'password.numbers'       => 'Password harus mengandung minimal satu angka',
            'password.symbols'       => 'Password harus mengandung minimal satu simbol (contoh: ! @ # $ %)',
            'password.uncompromised' => 'Password ini pernah muncul dalam kebocoran data. Gunakan password lain yang lebih aman.',
        ];
    }
}