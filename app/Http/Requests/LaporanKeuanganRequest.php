<?php

namespace App\Http\Requests;

use App\Support\Laporan\PeriodeLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter Laporan Keuangan. Dipakai bersama oleh tampilan, Excel, dan PDF,
 * jadi export selalu memakai filter yang sama dengan yang sedang dilihat di layar.
 * Semua lewat query string (GET), supaya URL bisa dibagikan.
 */
class LaporanKeuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    /** Filter yang tidak valid (mis. URL diketik manual) kembali ke halaman laporan dengan pesan error. */
    protected function getRedirectUrl(): string
    {
        return route('laporan.keuangan.index');
    }

    public function rules(): array
    {
        $rentang = $this->input('periode') === 'rentang';

        return [
            'periode'       => ['nullable', Rule::in(array_keys(PeriodeLaporan::PRESETS))],
            'dari'          => $rentang ? ['required', 'date_format:Y-m-d'] : ['nullable'],
            'sampai'        => $rentang ? ['required', 'date_format:Y-m-d', 'after_or_equal:dari'] : ['nullable'],

            'sumber_masuk'  => ['nullable', Rule::in(['penjualan', 'manual'])],
            'sumber_keluar' => ['nullable', Rule::in(['pembelian', 'operasional'])],
            'kategori'      => ['nullable', 'integer', Rule::exists('kategori_pengeluarans', 'id')],

            'tahun'         => ['nullable', 'integer', 'between:2000,2100'],
            'tab'           => ['nullable', Rule::in(['ringkasan', 'rincian'])],
            'halaman'       => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'dari.required'          => 'Isi tanggal mulai untuk rentang tanggal.',
            'sampai.required'        => 'Isi tanggal akhir untuk rentang tanggal.',
            'dari.date_format'       => 'Format tanggal mulai tidak valid.',
            'sampai.date_format'     => 'Format tanggal akhir tidak valid.',
            'sampai.after_or_equal'  => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
            'kategori.exists'        => 'Kategori pengeluaran tidak ditemukan.',
            'tahun.between'          => 'Tahun tidak valid.',
        ];
    }

    public function periode(): PeriodeLaporan
    {
        return PeriodeLaporan::make($this->input('periode'), $this->input('dari'), $this->input('sampai'));
    }
}
