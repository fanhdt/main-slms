<?php

namespace App\Domain\Booking\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_uuid'             => ['required', 'string', 'exists:labs,uuid'],
            'start_time'           => ['required', 'date', 'after:now'],
            'end_time'             => ['required', 'date', 'after:start_time'],
            'notes'                => ['nullable', 'string'],

            'items'                => ['required', 'array', 'min:1'],
            'items.*.service_uuid' => ['nullable', 'string', 'exists:services,uuid'],
            'items.*.package_uuid' => ['nullable', 'string', 'exists:packages,uuid'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Waktu mulai harus di masa depan.',
            'end_time.after'   => 'Waktu selesai harus setelah waktu mulai.',
            'items.required'   => 'Minimal harus ada 1 layanan atau paket yang dipilih.',
        ];
    }
}