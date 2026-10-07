{{--
    Tab "Per Transaksi". Mewarisi $transaksi (paginator), $rp, $r, $adaFilter, $barangId dari Laporan.Penjualan.index.
    Laba "-" = ada barang di transaksi itu yang harga modalnya tidak diketahui (total penjualan tetap benar).
--}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-5">
        <div>
            <h4 class="card-title mb-1">Per Transaksi</h4>
            <div class="text-muted fs-7">
                {{ $r['label'] }} &middot; urut tanggal terbaru
                @if ($barangId)
                    &middot; transaksi yang memuat barang terpilih
                @endif
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle mb-0">
                <thead class="bg-body-tertiary">
                    <tr class="text-uppercase fs-8 fw-semibold text-muted">
                        <th class="ps-6" style="width:50px">No</th>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th class="text-center">Item</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Bayar</th>
                        <th class="text-end">Kembalian</th>
                        <th class="text-end">Laba</th>
                        <th class="pe-6 text-center" style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksi as $t)
                        @php $laba = \App\Services\Laporan\LaporanPenjualanService::labaTransaksi($t); @endphp
                        <tr>
                            <td class="ps-6">{{ $transaksi->firstItem() + $loop->index }}</td>
                            <td>
                                <span class="fw-semibold font-monospace">{{ $t->code }}</span>
                                <div class="text-muted fs-8">{{ $t->payment_method_text }}</div>
                            </td>
                            <td class="text-nowrap">
                                {{ \App\Support\Laporan\Format::tanggal($t->sold_at) }}
                                <div class="text-muted fs-8">{{ $t->sold_at->format('H:i') }}{{ $t->user ? ' · ' . $t->user->name : '' }}</div>
                            </td>
                            <td>{{ $t->customer_name }}</td>
                            <td class="text-center">
                                {{ (int) $t->items_count }} macam
                                <div class="text-muted fs-8">{{ number_format((int) $t->qty_total, 0, ',', '.') }} pcs</div>
                            </td>
                            <td class="text-end fw-semibold text-nowrap">
                                {{ $rp($t->total) }}
                                @if ($t->discount_label)
                                    <div class="text-muted fs-8 fw-normal">diskon {{ $t->discount_label }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">{{ $rp($t->paid_amount) }}</td>
                            <td class="text-end text-nowrap">{{ $t->change_amount > 0 ? $rp($t->change_amount) : '-' }}</td>
                            <td class="text-end fw-semibold text-nowrap">
                                @if ($laba === null)
                                    <span class="text-muted" title="Ada barang yang harga modalnya tidak diketahui">-</span>
                                @else
                                    <span class="{{ $laba >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($laba) }}</span>
                                @endif
                            </td>
                            <td class="pe-6 text-center">
                                @can('penjualan-terjual.view')
                                    <button type="button" class="btn btn-sm btn-light-primary"
                                            data-bs-toggle="modal" data-bs-target="#modal-detail-terjual"
                                            data-url="{{ route('penjualan.terjual.show', $t) }}"
                                            data-code="{{ $t->code }}">View</button>
                                @else
                                    <span class="text-muted">-</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-10">
                                Tidak ada transaksi pada {{ $r['label'] }}{{ $adaFilter ? ' dengan filter yang dipilih' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="text-muted fs-7">
            @if ($transaksi->total() > 0)
                Menampilkan <strong>{{ $transaksi->firstItem() }}</strong> sampai
                <strong>{{ $transaksi->lastItem() }}</strong> dari
                <strong>{{ number_format($transaksi->total(), 0, ',', '.') }}</strong> transaksi
            @else
                Tidak ada data
            @endif
        </div>

        {{ $transaksi->links() }}
    </div>
</div>
