{{--
    Tab "Mutasi Stok". Mewarisi $mutasi (paginator), $kartu (null atau array), $periode, $tanpaHalaman, $r dari Laporan.Stok.index.
    Masuk = barang datang (Cek Paket). Keluar = penjualan. Koreksi / pembatalan penjualan dihitung sebagai masuk.
--}}
@php
    $tipeBadge = [
        'pembelian'         => 'badge-light-success',
        'pembelian_batal'   => 'badge-light-danger',
        'penjualan'         => 'badge-light-primary',
        'koreksi_penjualan' => 'badge-light-warning',
        'penyesuaian'       => 'badge-light-dark',
    ];
    $nf = fn ($n) => number_format($n, 0, ',', '.');
@endphp

{{-- ============ Kartu stok per barang (muncul setelah satu barang dipilih) ============ --}}
@if ($kartu)
    @php
        $info = $kartu['info'];
        $k    = $kartu['ringkasan'];
    @endphp

    <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-5">
            <div>
                <h4 class="card-title mb-1">
                    Kartu Stok: {{ $info->nama }}@if ($info->varian) <span class="text-muted fw-normal">— {{ $info->varian }}</span>@endif
                </h4>
                <div class="text-muted fs-7">
                    {{ $info->kategori }} &middot; {{ $periode->label() }} &middot; stok aktual {{ $nf($k['stok_aktual']) }} pcs
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('laporan.stok.index', array_merge($tanpaHalaman, ['barang' => null, 'tab' => 'mutasi'])) }}" class="btn btn-sm btn-light">
                    Lihat semua barang
                </a>
                @can('laporan-stok.export')
                    <a href="{{ route('laporan.stok.kartu', request()->query()) }}" class="btn btn-sm btn-light-success d-inline-flex align-items-center gap-1">
                        <i class="ki-outline ki-file-down fs-4"></i> Export kartu stok
                    </a>
                @endcan
            </div>
        </div>

        <div class="card-body pt-2">
            @unless ($k['cocok'])
                <div class="alert alert-warning d-flex align-items-start gap-3">
                    <i class="ki-outline ki-information-5 fs-2 text-warning mt-1"></i>
                    <div class="fs-7">
                        <strong>Saldo seluruh mutasi ({{ $nf($k['saldo_total']) }}) berbeda dengan stok aktual ({{ $nf($k['stok_aktual']) }}),
                        selisih {{ $k['selisih'] > 0 ? '+' : '' }}{{ $nf($k['selisih']) }} pcs.</strong>
                        Stok barang ini berubah di luar Cek Paket dan Terjual, misalnya diubah manual di Master Data.
                    </div>
                </div>
            @endunless

            <div class="row g-4 mb-6">
                <div class="col-6 col-lg-3">
                    <div class="bg-light rounded p-4">
                        <div class="text-muted fs-8">Saldo awal periode</div>
                        <div class="fs-3 fw-bold">{{ $nf($k['saldo_awal']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="bg-light rounded p-4">
                        <div class="text-muted fs-8">Masuk</div>
                        <div class="fs-3 fw-bold text-success">+{{ $nf($k['masuk']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="bg-light rounded p-4">
                        <div class="text-muted fs-8">Keluar</div>
                        <div class="fs-3 fw-bold text-danger">-{{ $nf($k['keluar']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="bg-light rounded p-4">
                        <div class="text-muted fs-8">Saldo akhir periode</div>
                        <div class="fs-3 fw-bold">{{ $nf($k['saldo_akhir']) }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-row-bordered align-middle gs-4 gy-3 mb-0">
                    <thead>
                        <tr class="fw-bold text-muted fs-8 text-uppercase bg-light">
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Referensi</th>
                            <th class="text-end">Masuk</th>
                            <th class="text-end">Keluar</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="text-muted">
                            <td colspan="5" class="fst-italic">Saldo awal periode (seluruh mutasi sebelum {{ \App\Support\Laporan\Format::tanggal($periode->from) }})</td>
                            <td class="text-end fw-bold text-nowrap">{{ $nf($k['saldo_awal']) }}</td>
                        </tr>
                        @forelse ($kartu['baris'] as $b)
                            <tr>
                                <td class="text-nowrap">
                                    {{ \App\Support\Laporan\Format::tanggal($b->tanggal) }}
                                    <div class="text-muted fs-8">{{ substr((string) $b->tanggal, 11, 5) }}{{ $b->oleh ? ' · ' . $b->oleh : '' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $tipeBadge[$b->tipe] ?? 'badge-light' }}">{{ $b->tipe_label }}</span>
                                    @if ($b->keterangan)
                                        <div class="text-muted fs-8 mt-1">{{ $b->keterangan }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($b->ref_code)
                                        @if ($b->ref_ada && $b->ref_type === 'terjual')
                                            @can('penjualan-terjual.view')
                                                <a href="#" class="fw-semibold font-monospace"
                                                   data-bs-toggle="modal" data-bs-target="#modal-detail-terjual"
                                                   data-url="{{ route('penjualan.terjual.show', $b->ref_id) }}"
                                                   data-code="{{ $b->ref_code }}">{{ $b->ref_code }}</a>
                                            @else
                                                <span class="fw-semibold font-monospace">{{ $b->ref_code }}</span>
                                            @endcan
                                        @elseif ($b->ref_ada && $b->ref_type === 'purchase')
                                            @can('cek-paket.view')
                                                <a href="{{ route('purchases.show', $b->ref_id) }}" class="fw-semibold font-monospace">{{ $b->ref_code }}</a>
                                            @else
                                                <span class="fw-semibold font-monospace">{{ $b->ref_code }}</span>
                                            @endcan
                                        @else
                                            <span class="font-monospace text-muted">{{ $b->ref_code }}</span>
                                            <div class="text-muted fs-8">dokumen sudah dihapus</div>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end text-success fw-semibold text-nowrap">{{ $b->masuk > 0 ? '+' . $nf($b->masuk) : '' }}</td>
                                <td class="text-end text-danger fw-semibold text-nowrap">{{ $b->keluar > 0 ? '-' . $nf($b->keluar) : '' }}</td>
                                <td class="text-end fw-bold text-nowrap">{{ $nf($b->saldo) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-8">Tidak ada mutasi pada {{ $periode->label() }}.</td>
                            </tr>
                        @endforelse
                        <tr class="fw-bold bg-light">
                            <td colspan="3">Saldo akhir periode</td>
                            <td class="text-end text-success text-nowrap">+{{ $nf($k['masuk']) }}</td>
                            <td class="text-end text-danger text-nowrap">-{{ $nf($k['keluar']) }}</td>
                            <td class="text-end text-nowrap">{{ $nf($k['saldo_akhir']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-5">
                <div class="text-muted fs-7">
                    @if ($kartu['baris']->total() > 0)
                        Menampilkan <strong>{{ $kartu['baris']->firstItem() }}</strong> sampai
                        <strong>{{ $kartu['baris']->lastItem() }}</strong> dari
                        <strong>{{ $nf($kartu['baris']->total()) }}</strong> mutasi
                    @endif
                </div>
                {{ $kartu['baris']->links() }}
            </div>
        </div>
    </div>
@endif

{{-- ============ Mutasi semua barang ============ --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-5">
        <div>
            <h4 class="card-title mb-1">Mutasi Stok per Barang</h4>
            <div class="text-muted fs-7">
                {{ $periode->label() }}
                @unless ($kartu)
                    &middot; klik nama barang atau pilih barang di filter untuk melihat kartu stoknya
                @endunless
            </div>
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
                        <th class="text-end">Saldo Awal</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Saldo Akhir</th>
                        <th class="text-end pe-6">Stok Aktual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mutasi as $m)
                        <tr>
                            <td class="ps-6">{{ $mutasi->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('laporan.stok.index', array_merge($tanpaHalaman, ['barang' => $m->key, 'tab' => 'mutasi'])) }}" class="fw-semibold">{{ $m->nama }}</a>
                                @if ($m->varian)
                                    <div class="text-muted fs-8">{{ $m->varian }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge-light-dark">{{ $m->kategori }}</span></td>
                            <td class="text-end text-nowrap">{{ $nf($m->saldo_awal) }}</td>
                            <td class="text-end text-success fw-semibold text-nowrap">{{ $m->masuk > 0 ? '+' . $nf($m->masuk) : '-' }}</td>
                            <td class="text-end text-danger fw-semibold text-nowrap">{{ $m->keluar > 0 ? '-' . $nf($m->keluar) : '-' }}</td>
                            <td class="text-end fw-bold text-nowrap">{{ $nf($m->saldo_akhir) }}</td>
                            <td class="text-end pe-6 text-nowrap">
                                {{ $nf($m->stok_aktual) }}
                                @if ($m->tidak_cocok)
                                    <i class="ki-outline ki-information-5 fs-4 text-warning ms-1" data-bs-toggle="tooltip"
                                       title="Saldo seluruh mutasi {{ $nf($m->saldo_total) }} berbeda dengan stok aktual {{ $nf($m->stok_aktual) }}. Stok diubah di luar Cek Paket dan Terjual."></i>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-10">
                                Tidak ada mutasi stok pada {{ $periode->label() }}{{ ($kategoriId ?? null) || ($barangKey ?? null) ? ' dengan filter yang dipilih' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="text-muted fs-7">
            @if ($mutasi->total() > 0)
                Menampilkan <strong>{{ $mutasi->firstItem() }}</strong> sampai
                <strong>{{ $mutasi->lastItem() }}</strong> dari
                <strong>{{ $nf($mutasi->total()) }}</strong> barang
            @else
                Tidak ada data
            @endif
        </div>

        {{ $mutasi->links() }}
    </div>
</div>