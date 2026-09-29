<?php

namespace App\Domain\Booking\Requests;

use App\Domain\Booking\Enums\BookingPurpose;
use App\Domain\Booking\Enums\BookingType;
use App\Domain\LabService\Models\Package;
use App\Domain\LabService\Models\Service;
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
            'start_time'   => ['nullable', 'date', 'after:now'],
            'end_time'     => ['nullable', 'date', 'after:start_time'],
            'notes'        => ['nullable', 'string'],
            'booking_type' => ['required', Rule::in($bookingTypes)],

            // --- Pinjam Lab ---
            'purpose' => [
                Rule::requiredIf(fn () => $this->input('booking_type') === BookingType::LabRental->value),
                Rule::in($purposes),
            ],
            'nim' => ['nullable', 'string', 'max:20'],

            // --- Sewa Alat ---
            'assets'             => [
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

            // --- Opsi tambahan untuk Service (misal: Edit Foto -> Retouch, Remove BG) ---
            'items.*.option_uuids'   => ['nullable', 'array'],
            'items.*.option_uuids.*' => ['nullable', 'string', 'exists:service_options,uuid'],

            // Dipakai kalau ada opsi dengan price_type = per_photo
            'items.*.photo_count' => ['nullable', 'integer', 'min:1'],

            // Harga custom
            'items.*.custom_prices'   => ['nullable', 'array'],
            'items.*.custom_prices.*' => ['nullable', 'numeric', 'min:0'],
            'items.*.custom_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('booking_type') !== BookingType::Service->value) {
                return;
            }

            $items = $this->input('items', []);
            $requiresSchedule = false;

            foreach ($items as $index => $item) {
                if (!empty($item['service_uuid'])) {
                    $service = Service::where('uuid', $item['service_uuid'])->first();
                    if ($service?->type->requiresSchedule()) {
                        $requiresSchedule = true;
                    }

                    // Pastikan tiap option_uuid yang dipilih benar-benar milik service ini,
                    // bukan option_uuid milik service lain yang nyasar/dipalsukan dari frontend.
                    if (!empty($item['option_uuids']) && $service) {
                        $validOptionUuids = $service->options()->pluck('uuid')->all();
                        foreach ($item['option_uuids'] as $optionUuid) {
                            if (!in_array($optionUuid, $validOptionUuids, true)) {
                                $validator->errors()->add(
                                    "items.{$index}.option_uuids",
                                    'Ada opsi tambahan yang tidak sesuai dengan layanan yang dipilih.'
                                );
                            }
                        }
                    }
                }

                if (!empty($item['package_uuid'])) {
                    $package = Package::with('items.service')
                        ->where('uuid', $item['package_uuid'])
                        ->first();

                    $hasScheduledService = $package?->items->contains(
                        fn ($pi) => $pi->service?->type->requiresSchedule()
                    );

                    if ($hasScheduledService) {
                        $requiresSchedule = true;
                    }
                }
            }

            if ($requiresSchedule && !$this->input('start_time')) {
                $validator->errors()->add('start_time', 'Jasa ini memerlukan pemilihan jadwal.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'start_time.after'      => 'Waktu mulai harus di masa depan.',
            'end_time.after'        => 'Waktu selesai harus setelah waktu mulai.',
            'items.required'        => 'Minimal harus ada 1 layanan atau paket yang dipilih.',
            'purpose.required'      => 'Keperluan peminjaman lab wajib dipilih.',
            'assets.required'       => 'Minimal harus ada 1 alat yang dipilih.',
        ];
    }
}