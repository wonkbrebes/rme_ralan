<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePendaftaranRequest extends FormRequest
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
            'patient_id'        => ['required', 'exists:patients,id'],
            'polyclinic_id'     => ['required', 'exists:polyclinics,id'],
            'doctor_id'         => ['required', 'exists:doctors,id'],
            'jenis_pembayaran'  => ['required', 'in:UMUM,BPJS'],
            'keluhan_utama'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'patient_id.required'       => 'Pasien harus dipilih.',
            'patient_id.exists'         => 'Pasien tidak ditemukan.',
            'polyclinic_id.required'    => 'Poliklinik harus dipilih.',
            'polyclinic_id.exists'      => 'Poliklinik tidak ditemukan.',
            'doctor_id.required'        => 'Dokter harus dipilih.',
            'doctor_id.exists'          => 'Dokter tidak ditemukan.',
            'jenis_pembayaran.required' => 'Jenis pembayaran harus dipilih.',
            'jenis_pembayaran.in'       => 'Jenis pembayaran harus UMUM atau BPJS.',
        ];
    }
}
