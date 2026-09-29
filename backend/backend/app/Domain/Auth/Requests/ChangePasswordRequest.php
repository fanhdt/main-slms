<?php

declare(strict_types=1);

namespace App\Domain\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
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
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.confirmed'        => 'Konfirmasi password baru tidak cocok.',
            'password.min'              => 'Password baru minimal 8 karakter.',
            'password.mixed'            => 'Password baru harus mengandung kombinasi huruf besar dan huruf kecil',
            'password.letters'          => 'Password baru harus mengandung minimal satu huruf',
            'password.numbers'          => 'Password baru harus mengandung minimal satu angka',
            'password.symbols'          => 'Password baru harus mengandung minimal satu simbol (contoh: ! @ # $ %)',
            'password.uncompromised'    => 'Password ini pernah muncul dalam kebocoran data. Gunakan password lain yang lebih aman.',
        ];
    }
}