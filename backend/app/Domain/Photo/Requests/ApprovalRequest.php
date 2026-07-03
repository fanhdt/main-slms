<?php

declare(strict_types=1);

namespace App\Domain\Photo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision'       => ['required', 'string', 'in:approve,revise'],
            'revision_note'  => ['required_if:decision,revise', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.in'             => 'Keputusan harus approve atau revise.',
            'revision_note.required_if' => 'Wajib isi catatan revisi kalau minta revisi.',
        ];
    }
}