<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keluhan_utama'    => ['required', 'string'],
            'riwayat_penyakit' => ['nullable', 'string'],
            'riwayat_alergi'   => ['nullable', 'string'],
            'pemeriksaan_fisik'=> ['nullable', 'string'],
            'catatan_dokter'   => ['nullable', 'string'],
            'tindak_lanjut'    => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'keluhan_utama.required' => 'Keluhan utama pasien wajib diisi.',
        ];
    }
}
