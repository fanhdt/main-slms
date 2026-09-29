<?php

declare(strict_types=1);

namespace App\Domain\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRfidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rfid_uid' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'rfid_uid')->ignore($this->route('uuid'), 'uuid'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rfid_uid.unique' => 'Kartu ini sudah terdaftar ke akun lain.',
        ];
    }
}