<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi tambah / ubah Pengeluaran OPERASIONAL (dipakai store dan update). */
class PengeluaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nominal' => preg_replace('/\D/', '', (string) $this->input('nominal')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],

            // "Kulakan" (is_system) sengaja tidak boleh dipilih: itu hanya dibuat otomatis dari Cek Paket
            'kategori_pengeluaran_id' => [
                'required', 'integer',
                Rule::exists('kategori_pengeluarans', 'id')->where('is_system', false),
            ],

            'keterangan'  => ['nullable', 'string', 'max:255'],
            'nominal'     => ['required', 'integer', 'min:1', 'max:1000000000000'],

            // foto nota, opsional. Batas 2 MB (dicek juga di browser sebelum diunggah).
            'bukti'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_bukti' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required'        => 'Tanggal wajib diisi.',
            'tanggal.date_format'     => 'Format tanggal tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini.',
            'kategori_pengeluaran_id.required' => 'Pilih kategori pengeluaran.',
            'kategori_pengeluaran_id.exists'   => 'Kategori tidak valid. Kategori "Kulakan" hanya dibuat otomatis dari Cek Paket.',
            'keterangan.max'          => 'Keterangan maksimal 255 karakter.',
            'nominal.required'        => 'Nominal wajib diisi.',
            'nominal.integer'         => 'Nominal harus berupa angka.',
            'nominal.min'             => 'Nominal minimal Rp 1.',
            'nominal.max'             => 'Nominal terlalu besar.',
            'bukti.max'               => 'Ukuran bukti maksimal 2 MB.',
            'bukti.mimes'             => 'Bukti harus berupa foto (jpg, png, atau webp).',
            'bukti.file'              => 'Bukti harus berupa file foto.',
            // dipakai PHP saat file melebihi upload_max_filesize di server
            'bukti.uploaded'          => 'Bukti gagal diunggah. Pastikan ukurannya tidak lebih dari 2 MB.',
        ];
    }
}
