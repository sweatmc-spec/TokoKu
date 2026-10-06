{{-- Isi modal Detail. Hanya baca; ubah lewat Edit atau Hapus. --}}
@php
    $rp = fn ($n) => \App\Models\Terjual::rupiah($n);
@endphp

<div class="row g-5 mb-7">
    <div class="col-6 col-md-4">
        <div class="text-gray-500 fs-8 fw-semibold mb-1">Kode transaksi</div>
        <div class="fw-bold text-gray-900">{{ $terjual->code }}</div>
    </div>
    <div class="col-6 col-md-4">
        <div class="text-gray-500 fs-8 fw-semibold mb-1">Tanggal</div>
        <div class="fw-bold text-gray-900">{{ $terjual->sold_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="col-6 col-md-4">
        <div class="text-gray-500 fs-8 fw-semibold mb-1">Customer</div>
        <div class="fw-bold text-gray-900">{{ $terjual->customer_name }}</div>
    </div>
    <div class="col-6 col-md-4">
        <div class="text-gray-500 fs-8 fw-semibold mb-1">Metode pembayaran</div>
        <span class="badge badge-light-primary fs-7">{{ $terjual->payment_method_text }}</span>
    </div>
    <div class="col-6 col-md-4">
        <div class="text-gray-500 fs-8 fw-semibold mb-1">Kasir</div>
        <div class="fw-bold text-gray-900">{{ $terjual->user?->name ?? '-' }}</div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-row-dashed align-middle gy-3 mb-0">
        <thead>
            <tr class="text-gray-500 fw-bold fs-7 gs-0">
                <th class="w-40px">No</th>
                <th>Barang</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Harga</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody class="text-gray-700 fw-semibold">
            @foreach ($terjual->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="text-gray-800 fw-bold">{{ $item->product_name }}</div>
                        @if ($item->variant_label)
                            <div class="text-muted fs-8">{{ $item->variant_label }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="text-end">{{ $rp($item->unit_price) }}</td>
                    <td class="text-end text-gray-900 fw-bold">{{ $rp($item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="separator separator-dashed my-6"></div>

<div class="row justify-content-end">
    <div class="col-md-8 col-lg-7">
        <div class="d-flex justify-content-between mb-3">
            <span class="text-gray-600">Subtotal</span>
            <span class="fw-semibold text-gray-900">{{ $rp($terjual->subtotal) }}</span>
        </div>

        @if ($terjual->discount_label)
            <div class="d-flex justify-content-between mb-3">
                <span class="text-gray-600">Diskon ({{ $terjual->discount_label }})</span>
                <span class="fw-semibold text-danger">- {{ $rp($terjual->discount_amount) }}</span>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center rounded px-4 py-3 mb-3 border"
             style="background: rgba(99, 102, 241, .12); background: color-mix(in srgb, var(--bs-primary) 12%, transparent);
                    border-color: rgba(99, 102, 241, .4) !important; border-color: color-mix(in srgb, var(--bs-primary) 40%, transparent) !important;">
            <span class="fw-bold text-gray-900">Total</span>
            <span class="fs-3 fw-bold text-primary">{{ $rp($terjual->total) }}</span>
        </div>

        <div class="d-flex justify-content-between mb-3">
            <span class="text-gray-600">Jumlah bayar</span>
            <span class="fw-semibold text-gray-900">{{ $rp($terjual->paid_amount) }}</span>
        </div>

        @if ($terjual->change_amount > 0)
            <div class="d-flex justify-content-between">
                <span class="text-gray-600">Kembalian</span>
                <span class="fw-semibold text-success">{{ $rp($terjual->change_amount) }}</span>
            </div>
        @endif
    </div>
</div>

@if ($terjual->note)
    <div class="border border-dashed border-gray-300 rounded p-4 mt-6">
        <div class="text-gray-500 fs-8 fw-semibold mb-1">Catatan</div>
        <div class="text-gray-800">{{ $terjual->note }}</div>
    </div>
@endif