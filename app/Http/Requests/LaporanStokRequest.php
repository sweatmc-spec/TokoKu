<?php

namespace App\Http\Requests;

use App\Support\Laporan\PeriodeLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter Laporan Stok. Dipakai bersama oleh tampilan, Excel, PDF, dan kartu stok.
 * Periode hanya berlaku untuk tab Mutasi Stok; tab Stok Saat Ini adalah kondisi hari ini.
 */
class LaporanStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    protected function getRedirectUrl(): string
    {
        return route('laporan.stok.index');
    }

    public function rules(): array
    {
        $rentang = $this->input('periode') === 'rentang';

        return [
            'periode'  => ['nullable', Rule::in(array_keys(PeriodeLaporan::PRESETS))],
            'dari'     => $rentang ? ['required', 'date_format:Y-m-d'] : ['nullable'],
            'sampai'   => $rentang ? ['required', 'date_format:Y-m-d', 'after_or_equal:dari'] : ['nullable'],

            'kategori' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            // kunci barang: "p:12" (produk) atau "v:34" (varian pakaian), sama seperti form Pembayaran
            'barang'   => ['nullable', 'regex:/^[pv]:\d+$/'],
            'status'   => ['nullable', Rule::in(['habis', 'menipis', 'aman'])],
            'q'        => ['nullable', 'string', 'max:100'],

            'tab'            => ['nullable', Rule::in(['stok', 'mutasi'])],
            'halaman_stok'   => ['nullable', 'integer', 'min:1'],
            'halaman_mutasi' => ['nullable', 'integer', 'min:1'],
            'halaman_kartu'  => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'dari.required'         => 'Isi tanggal mulai untuk rentang tanggal.',
            'sampai.required'       => 'Isi tanggal akhir untuk rentang tanggal.',
            'dari.date_format'      => 'Format tanggal mulai tidak valid.',
            'sampai.date_format'    => 'Format tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
            'kategori.exists'       => 'Kategori tidak ditemukan.',
            'barang.regex'          => 'Barang tidak valid.',
        ];
    }

    public function periode(): PeriodeLaporan
    {
        return PeriodeLaporan::make($this->input('periode'), $this->input('dari'), $this->input('sampai'));
    }
}