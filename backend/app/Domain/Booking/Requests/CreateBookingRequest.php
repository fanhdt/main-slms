<?php

namespace App\Domain\Booking\Requests;

use App\Domain\Booking\Enums\BookingPurpose;
use App\Domain\Booking\Enums\BookingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookingTypes = array_map(fn ($c) => $c->value, BookingType::cases());
        $purposes = array_map(fn ($c) => $c->value, BookingPurpose::cases());

        return [
            'lab_uuid'     => ['required', 'string', 'exists:labs,uuid'],
            'start_time'   => ['required', 'date', 'after:now'],
            'end_time'     => ['required', 'date', 'after:start_time'],
            'notes'        => ['nullable', 'string'],
            'booking_type' => ['required', Rule::in($bookingTypes)],

            // --- Pinjam Lab ---
            'purpose' => [
                Rule::requiredIf(fn () => $this->input('booking_type') === BookingType::LabRental->value),
                Rule::in($purposes),
            ],
            // Kalau user belum punya NIM tersimpan tapi klaim mahasiswa (purpose academic/organization)
            'nim' => ['nullable', 'string', 'max:20'],

            // --- Sewa Alat ---
           'assets'              => [
    Rule::requiredIf(fn () => $this->input('booking_type') === BookingType::AssetRental->value),
    'array', 'min:1',
],
'assets.*.asset_uuid' => ['required', 'string', 'exists:assets,uuid'],
'assets.*.quantity'   => ['required', 'integer', 'min:1'],

            // --- Jasa & Paket ---
            'items' => [
                Rule::requiredIf(fn () => $this->input('booking_type') === BookingType::Service->value),
                'array', 'min:1',
            ],
            'items.*.service_uuid' => ['nullable', 'string', 'exists:services,uuid'],
            'items.*.package_uuid' => ['nullable', 'string', 'exists:packages,uuid'],
            'items.*.quantity'     => ['required_with:items', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after'      => 'Waktu mulai harus di masa depan.',
            'end_time.after'        => 'Waktu selesai harus setelah waktu mulai.',
            'items.required'        => 'Minimal harus ada 1 layanan atau paket yang dipilih.',
            'purpose.required'      => 'Keperluan peminjaman lab wajib dipilih.',
            'assets.required'  => 'Minimal harus ada 1 alat yang dipilih.',
        ];
    }
}