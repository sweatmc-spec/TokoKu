{{--
    Tab "Rincian Transaksi": pemasukan dan pengeluaran dalam satu daftar berurut tanggal (UNION di database).
    Mewarisi $rincian (paginator), $rp, $r, $adaFilter dari Laporan.Keuangan.index.
--}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-5">
        <div>
            <h4 class="card-title mb-1">Rincian Transaksi</h4>
            <div class="text-muted fs-7">{{ $r['label'] }} &middot; urut tanggal terbaru</div>
        </div>
        <div class="fs-7">
            <span class="text-muted">Masuk:</span> <strong class="text-success text-nowrap">{{ $rp($r['pemasukan']) }}</strong>
            <span class="mx-2 text-muted">|</span>
            <span class="text-muted">Keluar:</span> <strong class="text-danger text-nowrap">{{ $rp($r['pengeluaran']) }}</strong>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle mb-0">
                <thead class="bg-body-tertiary">
                    <tr class="text-uppercase fs-8 fw-semibold text-muted">
                        <th class="ps-6" style="width:50px">No</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Sumber / Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end pe-6">Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rincian as $row)
                        @php $masuk = $row->jenis === 'masuk'; @endphp
                        <tr>
                            <td class="ps-6">{{ $rincian->firstItem() + $loop->index }}</td>
                            <td class="text-nowrap">{{ \App\Support\Laporan\Format::tanggal($row->tanggal) }}</td>
                            <td>
                                @if ($masuk)
                                    <span class="badge badge-light-success">Pemasukan</span>
                                @else
                                    <span class="badge badge-light-danger">Pengeluaran</span>
                                @endif
                            </td>
                            <td>
                                @if ($masuk)
                                    <span class="badge {{ $row->sumber === 'penjualan' ? 'badge-light-primary' : 'badge-light-info' }}">{{ \App\Support\Laporan\Format::sumber($row->sumber) }}</span>
                                @else
                                    <span class="badge badge-light-dark">{{ $row->kategori }}</span>
                                    <span class="badge {{ $row->sumber === 'pembelian' ? 'badge-light-warning' : 'badge-light-info' }} ms-1">{{ \App\Support\Laporan\Format::sumber($row->sumber) }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($row->ref_kode && $row->ref_tipe === 'terjual')
                                    @can('penjualan-terjual.view')
                                        <a href="#" class="fw-semibold font-monospace"
                                           data-bs-toggle="modal" data-bs-target="#modal-detail-terjual"
                                           data-url="{{ route('penjualan.terjual.show', $row->ref_id) }}"
                                           data-code="{{ $row->ref_kode }}">{{ $row->ref_kode }}</a>
                                    @else
                                        <span class="fw-semibold font-monospace">{{ $row->ref_kode }}</span>
                                    @endcan
                                    <div class="text-muted fs-8">{{ $row->keterangan }}</div>
                                @elseif ($row->ref_kode && $row->ref_tipe === 'purchase')
                                    @can('cek-paket.view')
                                        <a href="{{ route('purchases.show', $row->ref_id) }}" class="fw-semibold font-monospace">{{ $row->ref_kode }}</a>
                                    @else
                                        <span class="fw-semibold font-monospace">{{ $row->ref_kode }}</span>
                                    @endcan
                                    <div class="text-muted fs-8">{{ $row->keterangan }}</div>
                                @else
                                    {{ $row->keterangan ?: '-' }}
                                @endif
                            </td>
                            <td class="text-end text-success fw-semibold text-nowrap">{{ $masuk ? $rp($row->masuk) : '' }}</td>
                            <td class="text-end pe-6 text-danger fw-semibold text-nowrap">{{ $masuk ? '' : $rp($row->keluar) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-10">
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
            @if ($rincian->total() > 0)
                Menampilkan <strong>{{ $rincian->firstItem() }}</strong> sampai
                <strong>{{ $rincian->lastItem() }}</strong> dari
                <strong>{{ number_format($rincian->total(), 0, ',', '.') }}</strong> transaksi
            @else
                Tidak ada data
            @endif
        </div>

        {{ $rincian->links() }}
    </div>
</div>
