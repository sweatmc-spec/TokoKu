<?php

namespace App\Http\Requests;

use App\Support\Laporan\PeriodeLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter Laporan Penjualan. Dipakai bersama oleh tampilan, Excel, dan PDF
 * (semua lewat query string, jadi URL bisa dibagikan dan export memakai filter yang sama).
 */
class LaporanPenjualanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    protected function getRedirectUrl(): string
    {
        return route('laporan.penjualan.index');
    }

    public function rules(): array
    {
        $rentang = $this->input('periode') === 'rentang';

        return [
            'periode' => ['nullable', Rule::in(array_keys(PeriodeLaporan::PRESETS))],
            'dari'    => $rentang ? ['required', 'date_format:Y-m-d'] : ['nullable'],
            'sampai'  => $rentang ? ['required', 'date_format:Y-m-d', 'after_or_equal:dari'] : ['nullable'],

            'barang'  => ['nullable', 'integer', Rule::exists('products', 'id')],
            'kasir'   => ['nullable', 'integer', Rule::exists('users', 'id')],

            'tab'               => ['nullable', Rule::in(['transaksi', 'barang'])],
            'halaman_transaksi' => ['nullable', 'integer', 'min:1'],
            'halaman_barang'    => ['nullable', 'integer', 'min:1'],
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
            'barang.exists'         => 'Barang tidak ditemukan.',
            'kasir.exists'          => 'Kasir tidak ditemukan.',
        ];
    }

    public function periode(): PeriodeLaporan
    {
        return PeriodeLaporan::make($this->input('periode'), $this->input('dari'), $this->input('sampai'));
    }
}
