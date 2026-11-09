<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi tambah / ubah Pemasukan MANUAL (dipakai store dan update). */
class PemasukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    protected function prepareForValidation(): void
    {
        // input uang di modal diformat "1.500.000"; ambil angkanya saja
        $this->merge([
            'nominal' => preg_replace('/\D/', '', (string) $this->input('nominal')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal'    => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'keterangan' => ['required', 'string', 'max:255'],
            'nominal'    => ['required', 'integer', 'min:1', 'max:1000000000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required'        => 'Tanggal wajib diisi.',
            'tanggal.date_format'     => 'Format tanggal tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini.',
            'keterangan.required'     => 'Keterangan wajib diisi.',
            'keterangan.max'          => 'Keterangan maksimal 255 karakter.',
            'nominal.required'        => 'Nominal wajib diisi.',
            'nominal.integer'         => 'Nominal harus berupa angka.',
            'nominal.min'             => 'Nominal minimal Rp 1.',
            'nominal.max'             => 'Nominal terlalu besar.',
        ];
    }
}
