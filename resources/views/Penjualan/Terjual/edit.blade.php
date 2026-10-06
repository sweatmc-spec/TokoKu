@extends('layouts.app')

@section('title', 'Edit ' . $terjual->code)

@section('content')
    <div class="mb-5">
        <a href="{{ route('penjualan.terjual.index') }}" class="btn btn-sm btn-light">
            <i class="ki-outline ki-arrow-left fs-3"></i> Kembali ke Terjual
        </a>
    </div>

    <div class="d-flex align-items-start gap-3 border border-dashed border-warning rounded p-5 mb-6"
         style="background: rgba(246, 192, 0, .1); background: color-mix(in srgb, var(--bs-warning) 10%, transparent);">
        <i class="ki-outline ki-information-5 fs-2x text-warning"></i>
        <div class="fs-7 text-gray-700">
            <div class="fw-bold text-gray-900 mb-1">Perhatikan sebelum menyimpan</div>
            Saat disimpan, stok barang lama dikembalikan lalu dikurangi lagi sesuai isi form ini.
            Barang yang sudah ada di transaksi tetap memakai harga saat transaksi dibuat;
            barang yang baru ditambahkan memakai Profit Harga sekarang.
        </div>
    </div>

    @include('Penjualan.Pembayaran.partials._form', [
        'terjual'     => $terjual,
        'action'      => route('penjualan.terjual.update', $terjual),
        'method'      => 'PUT',
        'submitLabel' => 'Simpan perubahan',
    ])
@endsection