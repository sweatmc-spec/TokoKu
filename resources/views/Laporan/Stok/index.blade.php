@extends('layouts.app')

@section('title', 'Laporan Stok')

@push('styles')
    <style>
        .stat-card { border-left: 4px solid var(--bs-primary); }
        .stat-card.is-warn { border-left-color: var(--bs-warning); }
        .stat-card.is-bad { border-left-color: var(--bs-danger); }
        .stat-card.is-ok { border-left-color: var(--bs-success); }
        .stat-value { font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    @php
        $rp        = fn ($n) => \App\Support\Laporan\Format::rupiah($n);
        $r         = $ringkasan;
        $exportQs  = request()->query();
        $adaFilter = $kategoriId || $barangKey || $status || $cari;
        // link yang mempertahankan filter lain tetapi mengembalikan halaman ke 1
        $tanpaHalaman = request()->except('halaman_stok', 'halaman_mutasi', 'halaman_kartu');
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger mb-6">
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============ Judul + export ============ --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-4 mb-6">
        <div>
            <h3 class="fw-bold mb-1">Laporan Stok</h3>
            <div class="text-muted fs-7">
                Stok saat ini per {{ \App\Support\Laporan\Format::tanggal(now()) }}
                <span class="mx-1">&middot;</span>
                mutasi periode <strong class="text-gray-800">{{ $periode->presetLabel() }}</strong>: {{ $periode->label() }}
            </div>
        </div>

        @can('laporan-stok.export')
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('laporan.stok.excel', $exportQs) }}" class="btn btn-sm btn-light-success d-inline-flex align-items-center gap-1">
                    <i class="ki-outline ki-file-down fs-4"></i> Export Excel
                </a>
                <a href="{{ route('laporan.stok.pdf', $exportQs) }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-light-danger d-inline-flex align-items-center gap-1">
                    <i class="ki-outline ki-printer fs-4"></i> PDF / Cetak
                </a>
            </div>
        @endcan
    </div>

    {{-- ============ Filter ============ --}}
    <form method="GET" action="{{ route('laporan.stok.index') }}" id="form-laporan" autocomplete="off">
        <input type="hidden" name="tab" id="lp-tab" value="{{ $tab }}">

        <div class="card mb-6">
            <div class="card-body py-5">
                <div class="d-flex flex-wrap align-items-end gap-4">
                    @include('Laporan.partials._filter_periode', ['periode' => $periode])

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-kategori">Kategori</label>
                        <select name="kategori" id="lp-kategori" class="form-select form-select-sm" style="width:170px">
                            <option value=""></option>
                            @foreach ($kategoriOptions as $k)
                                <option value="{{ $k->id }}" @selected($kategoriId === $k->id)>{{ $k->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-barang">Barang</label>
                        <select name="barang" id="lp-barang" class="form-select form-select-sm" style="width:250px">
                            <option value=""></option>
                            @foreach ($barangOptions as $grup => $items)
                                <optgroup label="{{ $grup }}">
                                    @foreach ($items as $o)
                                        <option value="{{ $o['key'] }}" @selected($barangKey === $o['key'])>{{ $o['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-status">Status stok</label>
                        <select name="status" id="lp-status" class="form-select form-select-sm w-150px">
                            <option value="">Semua</option>
                            <option value="habis" @selected($status === 'habis')>Habis</option>
                            <option value="menipis" @selected($status === 'menipis')>Menipis</option>
                            <option value="aman" @selected($status === 'aman')>Aman</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-q">Cari nama barang</label>
                        <input type="text" name="q" id="lp-q" value="{{ $cari }}" class="form-control form-control-sm w-175px" placeholder="Ketik nama...">
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                        @if ($periode->key !== 'bulan-ini' || $adaFilter)
                            <a href="{{ route('laporan.stok.index') }}" class="btn btn-sm btn-light">Reset</a>
                        @endif
                    </div>
                </div>

                <div class="text-muted fs-8 mt-3">
                    Periode hanya berlaku untuk tab Mutasi Stok. Status stok dan pencarian hanya berlaku untuk tab Stok Saat Ini.
                    Kartu di bawah mengikuti filter kategori dan barang.
                </div>
            </div>
        </div>
    </form>

    {{-- ============ Peringatan: saldo mutasi tidak cocok dengan stok aktual ============ --}}
    @if ($r['tidak_cocok'] > 0)
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-6">
            <i class="ki-outline ki-information-5 fs-2 text-warning mt-1"></i>
            <div class="fs-7">
                <strong>{{ number_format($r['tidak_cocok'], 0, ',', '.') }} barang punya saldo mutasi yang berbeda dengan stok aktual.</strong>
                Artinya stok barang itu berubah di luar Cek Paket dan Terjual (mis. diubah manual di Master Data).
                Barangnya ditandai di tab
                <a href="{{ route('laporan.stok.index', array_merge($tanpaHalaman, ['tab' => 'mutasi'])) }}" class="fw-semibold">Mutasi Stok</a>.
            </div>
        </div>
    @endif

    {{-- ============ Kartu ringkasan ============ --}}
    <div class="row g-5 mb-6">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Jumlah Jenis Barang</div>
                    <div class="fs-2x fw-bold stat-value">{{ number_format($r['jenis'], 0, ',', '.') }}</div>
                    <div class="text-muted fs-8 mt-1">Total stok {{ number_format($r['total_pcs'], 0, ',', '.') }} pcs</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card {{ $r['menipis'] > 0 ? 'is-warn' : 'is-ok' }} h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Barang Menipis</div>
                    <div class="fs-2x fw-bold stat-value {{ $r['menipis'] > 0 ? 'text-warning' : '' }}">{{ number_format($r['menipis'], 0, ',', '.') }}</div>
                    <div class="text-muted fs-8 mt-1">
                        Stok di atas 0 dan sampai batas minimum
                        @if ($r['menipis'] > 0)
                            <br><a href="{{ route('laporan.stok.index', array_merge($tanpaHalaman, ['status' => 'menipis', 'tab' => 'stok'])) }}">Lihat daftarnya</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card {{ $r['habis'] > 0 ? 'is-bad' : 'is-ok' }} h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Barang Habis</div>
                    <div class="fs-2x fw-bold stat-value {{ $r['habis'] > 0 ? 'text-danger' : '' }}">{{ number_format($r['habis'], 0, ',', '.') }}</div>
                    <div class="text-muted fs-8 mt-1">
                        Stok 0
                        @if ($r['habis'] > 0)
                            <br><a href="{{ route('laporan.stok.index', array_merge($tanpaHalaman, ['status' => 'habis', 'tab' => 'stok'])) }}">Lihat daftarnya</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card is-ok h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Total Nilai Stok</div>
                    <div class="fs-2x fw-bold stat-value text-success">{{ $rp($r['nilai']) }}</div>
                    <div class="text-muted fs-8 mt-1">
                        Stok x harga beli terakhir per pcs
                        @if ($r['tanpa_harga'] > 0)
                            <br><span class="text-warning">{{ $r['tanpa_harga'] }} barang belum punya harga beli (tidak dihitung)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Empty state ============ --}}
    @unless ($r['ada_data'])
        <div class="card mb-6">
            <div class="card-body text-center py-12">
                <i class="ki-outline ki-parcel fs-3x text-gray-400 mb-4"></i>
                <div class="fw-bold fs-4 mb-2">Belum ada stok{{ $adaFilter ? ' yang cocok dengan filter' : '' }}</div>
                <div class="text-muted fs-7">
                    Barang muncul di sini setelah dicentang datang di Cek Paket.
                    @if ($adaFilter) Coba ubah atau reset filter di atas. @endif
                </div>
            </div>
        </div>
    @endunless

    {{-- ============ Tab ============ --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-6 fs-6" id="lp-tabs">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'stok' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-stok" data-tab="stok">
                Stok Saat Ini
                <span class="badge badge-light-primary ms-1">{{ number_format($stok->total(), 0, ',', '.') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'mutasi' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-mutasi" data-tab="mutasi">
                Mutasi Stok
                @if ($r['tidak_cocok'] > 0)
                    <span class="badge badge-light-warning ms-1">{{ $r['tidak_cocok'] }} tidak cocok</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $tab === 'stok' ? 'show active' : '' }}" id="tab-stok" role="tabpanel">
            @include('Laporan.Stok.partials._stok')
        </div>

        <div class="tab-pane fade {{ $tab === 'mutasi' ? 'show active' : '' }}" id="tab-mutasi" role="tabpanel">
            @include('Laporan.Stok.partials._mutasi')
        </div>
    </div>

    @can('penjualan-terjual.view')
        {{-- modal detail Terjual yang sama dengan halaman Pemasukan dan Laporan lain --}}
        @include('Keuangan.Pemasukan.partials._modal_terjual')
    @endcan
@endsection

@push('scripts')
<script>
(function () {
    // select2 kategori & barang (bisa dicari). Manual supaya tidak ganda dengan auto-init Metronic.
    if ($.fn.select2) {
        $('#lp-kategori').select2({ width: '170px', placeholder: 'Semua kategori', allowClear: true });
        $('#lp-barang').select2({ width: '250px', placeholder: 'Semua barang', allowClear: true });
    }

    // ingat tab aktif: ikut terkirim saat filter diterapkan
    $('#lp-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        $('#lp-tab').val($(this).data('tab'));
    });
})();
</script>
@endpush