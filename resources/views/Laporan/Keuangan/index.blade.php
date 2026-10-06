@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@push('styles')
    <style>
        .stat-card { border-left: 4px solid var(--bs-gray-400); }
        .stat-card.is-masuk { border-left-color: var(--bs-success); }
        .stat-card.is-keluar { border-left-color: var(--bs-danger); }
        .stat-card.is-laba { border-left-color: var(--bs-success); }
        .stat-card.is-rugi { border-left-color: var(--bs-danger); }
        .stat-card.is-info { border-left-color: var(--bs-primary); }
        .stat-value { font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    @php
        $rp        = fn ($n) => \App\Support\Laporan\Format::rupiah($n);
        $r         = $ringkasan;
        $laba      = $r['selisih'] >= 0;
        $exportQs  = request()->query();
        $adaFilter = $sumberMasuk || $sumberKeluar || $kategori;
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
            <h3 class="fw-bold mb-1">Laporan Keuangan</h3>
            <div class="text-muted fs-7">
                Periode <strong class="text-gray-800">{{ $periode->presetLabel() }}</strong>:
                {{ $r['label'] }}
                <span class="mx-1">&middot;</span> pembanding: {{ $r['label_lalu'] }}
            </div>
        </div>

        @can('laporan-keuangan.export')
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('laporan.keuangan.excel', $exportQs) }}" class="btn btn-sm btn-light-success d-inline-flex align-items-center gap-1">
                    <i class="ki-outline ki-file-down fs-4"></i> Export Excel
                </a>
                <a href="{{ route('laporan.keuangan.pdf', $exportQs) }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-light-danger d-inline-flex align-items-center gap-1">
                    <i class="ki-outline ki-printer fs-4"></i> PDF / Cetak
                </a>
            </div>
        @endcan
    </div>

    {{-- ============ Filter ============ --}}
    <form method="GET" action="{{ route('laporan.keuangan.index') }}" id="form-laporan" autocomplete="off">
        <input type="hidden" name="tab" id="lp-tab" value="{{ $tab }}">

        <div class="card mb-6">
            <div class="card-body py-5">
                <div class="d-flex flex-wrap align-items-end gap-4">
                    @include('Laporan.partials._filter_periode', ['periode' => $periode])

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-sumber-masuk">Sumber pemasukan</label>
                        <select name="sumber_masuk" id="lp-sumber-masuk" class="form-select form-select-sm w-150px">
                            <option value="">Semua</option>
                            <option value="penjualan" @selected($sumberMasuk === 'penjualan')>Penjualan</option>
                            <option value="manual" @selected($sumberMasuk === 'manual')>Manual</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-sumber-keluar">Sumber pengeluaran</label>
                        <select name="sumber_keluar" id="lp-sumber-keluar" class="form-select form-select-sm w-150px">
                            <option value="">Semua</option>
                            <option value="pembelian" @selected($sumberKeluar === 'pembelian')>Pembelian</option>
                            <option value="operasional" @selected($sumberKeluar === 'operasional')>Operasional</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-kategori">Kategori pengeluaran</label>
                        <select name="kategori" id="lp-kategori" class="form-select form-select-sm" style="width:190px">
                            <option value=""></option>
                            @foreach ($kategoris as $k)
                                <option value="{{ $k->id }}" @selected($kategori === $k->id)>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-tahun">Tahun (grafik bulanan)</label>
                        <select name="tahun" id="lp-tahun" class="form-select form-select-sm w-125px">
                            @foreach ($tahunOptions as $th)
                                <option value="{{ $th }}" @selected($th === $tahun)>{{ $th }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                        @if ($periode->key !== 'bulan-ini' || $adaFilter)
                            <a href="{{ route('laporan.keuangan.index') }}" class="btn btn-sm btn-light">Reset</a>
                        @endif
                    </div>
                </div>

                <div class="text-muted fs-8 mt-3">
                    Filter sumber dan kategori hanya menyaring sisinya sendiri (sumber pemasukan untuk pemasukan;
                    sumber pengeluaran dan kategori untuk pengeluaran).
                </div>
            </div>
        </div>
    </form>

    {{-- ============ Catatan: laba kas ============ --}}
    <div class="notice d-flex align-items-start gap-3 bg-light-primary rounded border border-primary border-dashed p-5 mb-6">
        <i class="ki-outline ki-information-5 fs-2 text-primary mt-1"></i>
        <div class="fs-7 text-gray-700">
            Laporan ini adalah <strong>laba kas sederhana</strong>: selisih uang masuk dan uang keluar, bukan laporan akuntansi lengkap.
            Bulan ketika Anda kulakan banyak bisa tampak rugi walau barangnya masih ada di stok dan belum terjual.
        </div>
    </div>

    {{-- ============ Kartu ringkasan ============ --}}
    <div class="row g-5 mb-6">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card is-masuk h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Total Pemasukan</div>
                    <div class="fs-2x fw-bold stat-value text-success">{{ $rp($r['pemasukan']) }}</div>
                    <div class="text-muted fs-8 mt-1 mb-2">{{ number_format($r['jumlah_masuk'], 0, ',', '.') }} transaksi &middot; sebelumnya {{ $rp($r['pemasukan_lalu']) }}</div>
                    @include('Laporan.partials._delta', ['pct' => $r['pct_pemasukan'], 'naikBaik' => true])
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card is-keluar h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Total Pengeluaran</div>
                    <div class="fs-2x fw-bold stat-value text-danger">{{ $rp($r['pengeluaran']) }}</div>
                    <div class="text-muted fs-8 mt-1 mb-2">{{ number_format($r['jumlah_keluar'], 0, ',', '.') }} transaksi &middot; sebelumnya {{ $rp($r['pengeluaran_lalu']) }}</div>
                    @include('Laporan.partials._delta', ['pct' => $r['pct_pengeluaran'], 'naikBaik' => false])
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card {{ $laba ? 'is-laba' : 'is-rugi' }} h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Selisih ({{ $laba ? 'Laba' : 'Rugi' }})</div>
                    <div class="fs-2x fw-bold stat-value {{ $laba ? 'text-success' : 'text-danger' }}">{{ $rp($r['selisih']) }}</div>
                    <div class="text-muted fs-8 mt-1">
                        {{ $laba ? 'Uang masuk lebih besar dari uang keluar.' : 'Uang keluar lebih besar dari uang masuk.' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card is-info h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Perubahan dibanding periode lalu</div>
                    @if ($r['pct_selisih'] === null)
                        <div class="fs-2x fw-bold text-muted">-</div>
                        <div class="text-muted fs-8 mt-1">Periode pembanding belum punya selisih untuk dibandingkan.</div>
                    @else
                        <div class="fs-2x fw-bold stat-value {{ $r['pct_selisih'] >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="ki-outline {{ $r['pct_selisih'] >= 0 ? 'ki-arrow-up' : 'ki-arrow-down' }} fs-2"></i>
                            {{ number_format(abs($r['pct_selisih']), 1, ',', '.') }}%
                        </div>
                    @endif
                    <div class="text-muted fs-8 mt-1">
                        Selisih sebelumnya: {{ $rp($r['selisih_lalu']) }}
                        <br>({{ $r['label_lalu'] }})
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Empty state: tidak ada transaksi pada periode ============ --}}
    @unless ($r['ada_data'])
        <div class="card mb-6">
            <div class="card-body text-center py-12">
                <i class="ki-outline ki-notepad fs-3x text-gray-400 mb-4"></i>
                <div class="fw-bold fs-4 mb-2">Tidak ada transaksi pada periode ini</div>
                <div class="text-muted fs-7">
                    Belum ada pemasukan maupun pengeluaran pada {{ $r['label'] }}{{ $adaFilter ? ' dengan filter yang dipilih' : '' }}.
                    Coba ubah periode atau filter di atas.
                </div>
            </div>
        </div>
    @endunless

    {{-- ============ Tab ============ --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-6 fs-6" id="lp-tabs">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'ringkasan' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-ringkasan" data-tab="ringkasan">Ringkasan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'rincian' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-rincian" data-tab="rincian">
                Rincian Transaksi
                <span class="badge badge-light-primary ms-1">{{ number_format($rincian->total(), 0, ',', '.') }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $tab === 'ringkasan' ? 'show active' : '' }}" id="tab-ringkasan" role="tabpanel">
            @include('Laporan.Keuangan.partials._ringkasan')
        </div>

        <div class="tab-pane fade {{ $tab === 'rincian' ? 'show active' : '' }}" id="tab-rincian" role="tabpanel">
            @include('Laporan.Keuangan.partials._rincian')
        </div>
    </div>

    @can('penjualan-terjual.view')
        {{-- modal detail Terjual yang sama dengan halaman Pemasukan --}}
        @include('Keuangan.Pemasukan.partials._modal_terjual')
    @endcan
@endsection

@push('scripts')
<script>
(function () {
    // select2 kategori (bisa dicari). Diinisialisasi manual supaya tidak ganda dengan auto-init Metronic.
    if ($.fn.select2) {
        $('#lp-kategori').select2({ width: '190px', placeholder: 'Semua kategori', allowClear: true });
    }

    // ingat tab aktif: ikut terkirim saat filter diterapkan
    $('#lp-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        $('#lp-tab').val($(this).data('tab'));
    });
})();
</script>
@endpush
