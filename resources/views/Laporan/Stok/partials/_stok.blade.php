{{--
    Tab "Stok Saat Ini". Mewarisi $stok (paginator), $stokTotal, $rp, $adaFilter dari Laporan.Stok.index.
    Habis dan Menipis paling atas. Nilai stok "-" = barang belum punya harga beli.
--}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-5">
        <div>
            <h4 class="card-title mb-1">Stok Saat Ini</h4>
            <div class="text-muted fs-7">Habis dan menipis paling atas, supaya langsung terlihat apa yang perlu dipesan ke sales</div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle mb-0">
                <thead class="bg-body-tertiary">
                    <tr class="text-uppercase fs-8 fw-semibold text-muted">
                        <th class="ps-6" style="width:50px">No</th>
                        <th>Barang</th>
                        <th>Kategori</th>
                        <th class="text-end">Stok</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Nilai Stok</th>
                        <th class="text-center pe-6">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stok as $b)
                        <tr>
                            <td class="ps-6">{{ $stok->firstItem() + $loop->index }}</td>
                            <td>
                                <span class="fw-semibold">{{ $b->nama }}</span>
                                @if ($b->varian)
                                    <div class="text-muted fs-8">{{ $b->varian }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge-light-dark">{{ $b->kategori }}</span></td>
                            <td class="text-end fw-bold text-nowrap {{ $b->status === 'habis' ? 'text-danger' : ($b->status === 'menipis' ? 'text-warning' : '') }}">
                                {{ number_format($b->stock, 0, ',', '.') }}
                                <div class="text-muted fs-8 fw-normal">min {{ number_format($b->stok_minimum, 0, ',', '.') }}</div>
                            </td>
                            <td class="text-muted">pcs</td>
                            <td class="text-end text-nowrap">
                                {{ $b->last_cost === null ? '-' : 'Rp ' . number_format($b->last_cost, 2, ',', '.') }}
                            </td>
                            <td class="text-end fw-semibold text-nowrap">
                                {{ $b->nilai === null ? '-' : $rp($b->nilai) }}
                            </td>
                            <td class="text-center pe-6">
                                @if ($b->status === 'habis')
                                    <span class="badge badge-light-danger">Habis</span>
                                @elseif ($b->status === 'menipis')
                                    <span class="badge badge-light-warning">Menipis</span>
                                @else
                                    <span class="badge badge-light-success">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-10">
                                {{ $adaFilter ? 'Tidak ada barang yang cocok dengan filter.' : 'Belum ada stok. Barang muncul setelah dicentang datang di Cek Paket.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($stok->total() > 0)
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="ps-6" colspan="3">
                                Total {{ $adaFilter ? 'sesuai filter' : 'semua barang' }}
                                <span class="text-muted fw-normal">({{ number_format($stokTotal['jumlah'], 0, ',', '.') }} jenis)</span>
                            </td>
                            <td class="text-end text-nowrap">{{ number_format($stokTotal['pcs'], 0, ',', '.') }}</td>
                            <td class="text-muted fw-normal">pcs</td>
                            <td></td>
                            <td class="text-end text-success text-nowrap">{{ $rp($stokTotal['nilai']) }}</td>
                            <td class="pe-6"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="text-muted fs-7">
            @if ($stok->total() > 0)
                Menampilkan <strong>{{ $stok->firstItem() }}</strong> sampai
                <strong>{{ $stok->lastItem() }}</strong> dari
                <strong>{{ number_format($stok->total(), 0, ',', '.') }}</strong> barang
                @if ($stokTotal['tanpa_harga'] > 0)
                    <span class="mx-1">&middot;</span>
                    <span class="text-warning">{{ $stokTotal['tanpa_harga'] }} barang belum punya harga beli, tidak masuk nilai stok</span>
                @endif
            @else
                Tidak ada data
            @endif
        </div>

        {{ $stok->links() }}
    </div>
</div>