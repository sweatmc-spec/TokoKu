{{--
    Tab "Per Barang". Mewarisi $barang (paginator), $rp, $r, $adaFilter dari Laporan.Penjualan.index.
    Urut default: total penjualan terbesar. Satu baris = satu barang (varian untuk pakaian).
    Laba "-" = modal barang itu tidak diketahui. "sebagian" = hanya sebagian barisnya yang punya modal.
--}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-5">
        <div>
            <h4 class="card-title mb-1">Per Barang</h4>
            <div class="text-muted fs-7">{{ $r['label'] }} &middot; penjualan terbesar dulu (setelah pembagian diskon)</div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle mb-0">
                <thead class="bg-body-tertiary">
                    <tr class="text-uppercase fs-8 fw-semibold text-muted">
                        <th class="ps-6" style="width:50px">No</th>
                        <th>Barang</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Total Penjualan</th>
                        <th class="text-end">Total Modal</th>
                        <th class="text-end">Laba</th>
                        <th class="text-end pe-6">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barang as $b)
                        <tr>
                            <td class="ps-6">{{ $barang->firstItem() + $loop->index }}</td>
                            <td>
                                <span class="fw-semibold">{{ $b->nama }}</span>
                                @if ($b->varian)
                                    <div class="text-muted fs-8">{{ $b->varian }}</div>
                                @endif
                                @if (! $b->product_id)
                                    <div class="text-muted fs-8">Produk sudah dihapus dari Master Data</div>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($b->qty, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold text-nowrap">{{ $rp($b->penjualan) }}</td>
                            <td class="text-end text-nowrap">
                                @if ($b->laba === null)
                                    <span class="text-muted">-</span>
                                @else
                                    {{ $rp($b->modal_int) }}
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-nowrap">
                                @if ($b->laba === null)
                                    <span class="text-muted" title="Harga modal barang ini tidak diketahui">-</span>
                                @else
                                    <span class="{{ $b->laba >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($b->laba) }}</span>
                                    @if ($b->sebagian)
                                        <div class="text-muted fs-8 fw-normal">sebagian</div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-end pe-6">
                                {{ $b->margin === null ? '-' : \App\Support\Laporan\Format::porsi($b->margin) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-10">
                                Tidak ada penjualan pada {{ $r['label'] }}{{ $adaFilter ? ' dengan filter yang dipilih' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($barang->total() > 0)
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="ps-6" colspan="2">Total semua barang</td>
                            <td class="text-end">{{ number_format($r['qty'], 0, ',', '.') }}</td>
                            <td class="text-end text-nowrap">{{ $rp($r['penjualan']) }}</td>
                            <td class="text-end text-nowrap">{{ $r['margin'] === null ? '-' : $rp($r['modal']) }}</td>
                            <td class="text-end text-nowrap {{ $r['margin'] === null ? '' : ($r['laba'] >= 0 ? 'text-success' : 'text-danger') }}">{{ $r['margin'] === null ? '-' : $rp($r['laba']) }}</td>
                            <td class="text-end pe-6">{{ $r['margin'] === null ? '-' : \App\Support\Laporan\Format::porsi($r['margin']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="text-muted fs-7">
            @if ($barang->total() > 0)
                Menampilkan <strong>{{ $barang->firstItem() }}</strong> sampai
                <strong>{{ $barang->lastItem() }}</strong> dari
                <strong>{{ number_format($barang->total(), 0, ',', '.') }}</strong> barang
            @else
                Tidak ada data
            @endif
        </div>

        {{ $barang->links() }}
    </div>
</div>
