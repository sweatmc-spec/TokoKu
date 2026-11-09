@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@push('styles')
    <style>
        .stat-card { border-left: 4px solid var(--bs-primary); }
        .stat-card.is-laba { border-left-color: var(--bs-success); }
        .stat-card.is-rugi { border-left-color: var(--bs-danger); }
        .stat-card.is-warn { border-left-color: var(--bs-warning); }
        .stat-value { font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    @php
        $rp        = fn ($n) => \App\Support\Laporan\Format::rupiah($n);
        $r         = $ringkasan;
        $exportQs  = request()->query();
        $adaFilter = $barangId || $kasirId;
        $labaAda   = $r['margin'] !== null;           // ada barang yang modalnya diketahui
        $laba      = $r['laba'] >= 0;
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
            <h3 class="fw-bold mb-1">Laporan Penjualan</h3>
            <div class="text-muted fs-7">
                Periode <strong class="text-gray-800">{{ $periode->presetLabel() }}</strong>:
                {{ $r['label'] }}
                <span class="mx-1">&middot;</span> pembanding: {{ $r['label_lalu'] }}
            </div>
        </div>

        @can('laporan-penjualan.export')
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('laporan.penjualan.excel', $exportQs) }}" class="btn btn-sm btn-light-success d-inline-flex align-items-center gap-1">
                    <i class="ki-outline ki-file-down fs-4"></i> Export Excel
                </a>
                <a href="{{ route('laporan.penjualan.pdf', $exportQs) }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-light-danger d-inline-flex align-items-center gap-1">
                    <i class="ki-outline ki-printer fs-4"></i> PDF / Cetak
                </a>
            </div>
        @endcan
    </div>

    {{-- ============ Filter ============ --}}
    <form method="GET" action="{{ route('laporan.penjualan.index') }}" id="form-laporan" autocomplete="off">
        <input type="hidden" name="tab" id="lp-tab" value="{{ $tab }}">

        <div class="card mb-6">
            <div class="card-body py-5">
                <div class="d-flex flex-wrap align-items-end gap-4">
                    @include('Laporan.partials._filter_periode', ['periode' => $periode])

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-barang">Barang</label>
                        <select name="barang" id="lp-barang" class="form-select form-select-sm" style="width:220px">
                            <option value=""></option>
                            @foreach ($barangOptions as $b)
                                <option value="{{ $b->id }}" @selected($barangId === (int) $b->id)>{{ $b->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-kasir">Kasir</label>
                        <select name="kasir" id="lp-kasir" class="form-select form-select-sm" style="width:190px">
                            <option value=""></option>
                            @foreach ($kasirOptions as $k)
                                <option value="{{ $k->id }}" @selected($kasirId === $k->id)>{{ $k->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                        @if ($periode->key !== 'bulan-ini' || $adaFilter)
                            <a href="{{ route('laporan.penjualan.index') }}" class="btn btn-sm btn-light">Reset</a>
                        @endif
                    </div>
                </div>

                @if ($barangId)
                    <div class="text-muted fs-8 mt-3">
                        Dengan filter barang, kartu, grafik, dan tab Per Barang hanya menghitung barang itu.
                        Tab Per Transaksi menampilkan transaksi utuh yang memuat barang tersebut.
                    </div>
                @endif
            </div>
        </div>
    </form>

    {{-- ============ Catatan laba ============ --}}
    <div class="notice d-flex align-items-start gap-3 bg-light-primary rounded border border-primary border-dashed p-5 mb-6">
        <i class="ki-outline ki-information-5 fs-2 text-primary mt-1"></i>
        <div class="fs-7 text-gray-700">
            <strong>Laba kotor</strong> = penjualan (setelah diskon) dikurangi harga modal. Harga modal adalah harga beli per pcs
            terakhir yang tercatat saat transaksi terjadi, dan tidak berubah walau harga beli naik atau turun kemudian.
            Diskon transaksi dibagi ke tiap barang sesuai nilainya.
        </div>
    </div>

    @if ($r['baris_tanpa_modal'] > 0)
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-6">
            <i class="ki-outline ki-information-5 fs-2 text-warning mt-1"></i>
            <div class="fs-7">
                <strong>{{ number_format($r['baris_tanpa_modal'], 0, ',', '.') }} baris barang belum punya harga modal</strong>
                (nilai penjualan {{ $rp($r['penjualan_tanpa_modal']) }}). Penjualannya tetap dihitung, tetapi tidak ikut dalam laba dan
                tampil "-" di tabel. Harga modal terisi dari harga beli saat barang dicentang datang di Cek Paket.
            </div>
        </div>
    @endif

    {{-- ============ Kartu ringkasan ============ --}}
    <div class="row g-5 mb-6">
        <div class="col-md-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Total Penjualan</div>
                    <div class="fs-2 fw-bold stat-value">{{ $rp($r['penjualan']) }}</div>
                    <div class="text-muted fs-8 mt-1 mb-2">Sebelumnya {{ $rp($r['penjualan_lalu']) }}</div>
                    @include('Laporan.partials._delta', ['pct' => $r['pct_penjualan'], 'naikBaik' => true])
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Jumlah Transaksi</div>
                    <div class="fs-2 fw-bold stat-value">{{ number_format($r['transaksi'], 0, ',', '.') }}</div>
                    <div class="text-muted fs-8 mt-1 mb-2">Sebelumnya {{ number_format($r['transaksi_lalu'], 0, ',', '.') }}</div>
                    @include('Laporan.partials._delta', ['pct' => $r['pct_transaksi'], 'naikBaik' => true])
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Rata-rata per Transaksi</div>
                    <div class="fs-2 fw-bold stat-value">{{ $rp($r['rata']) }}</div>
                    <div class="text-muted fs-8 mt-1 mb-2">Sebelumnya {{ $rp($r['rata_lalu']) }}</div>
                    @include('Laporan.partials._delta', ['pct' => $r['pct_rata'], 'naikBaik' => true])
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Total Qty Terjual</div>
                    <div class="fs-2 fw-bold stat-value">{{ number_format($r['qty'], 0, ',', '.') }} <span class="fs-6 text-muted">pcs</span></div>
                    <div class="text-muted fs-8 mt-1 mb-2">Sebelumnya {{ number_format($r['qty_lalu'], 0, ',', '.') }} pcs</div>
                    @include('Laporan.partials._delta', ['pct' => $r['pct_qty'], 'naikBaik' => true])
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl">
            <div class="card stat-card {{ ! $labaAda ? 'is-warn' : ($laba ? 'is-laba' : 'is-rugi') }} h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Laba Kotor</div>
                    @if ($labaAda)
                        <div class="fs-2 fw-bold stat-value {{ $laba ? 'text-success' : 'text-danger' }}">{{ $rp($r['laba']) }}</div>
                        <div class="text-muted fs-8 mt-1 mb-2">
                            Margin <strong class="text-gray-800">{{ \App\Support\Laporan\Format::porsi($r['margin']) }}</strong>
                            &middot; modal {{ $rp($r['modal']) }}
                        </div>
                        @include('Laporan.partials._delta', ['pct' => $r['pct_laba'], 'naikBaik' => true])
                    @else
                        <div class="fs-2 fw-bold text-muted">-</div>
                        <div class="text-muted fs-8 mt-1">
                            {{ $r['baris_tanpa_modal'] > 0 ? 'Harga modal belum tercatat untuk barang pada periode ini.' : 'Belum ada penjualan pada periode ini.' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Empty state ============ --}}
    @unless ($r['ada_data'])
        <div class="card mb-6">
            <div class="card-body text-center py-12">
                <i class="ki-outline ki-basket fs-3x text-gray-400 mb-4"></i>
                <div class="fw-bold fs-4 mb-2">Tidak ada penjualan pada periode ini</div>
                <div class="text-muted fs-7">
                    Belum ada transaksi pada {{ $r['label'] }}{{ $adaFilter ? ' dengan filter yang dipilih' : '' }}.
                    Coba ubah periode atau filter di atas.
                </div>
            </div>
        </div>
    @endunless

    {{-- ============ Grafik ============ --}}
    @if ($r['ada_data'])
        <div class="card mb-6">
            <div class="card-header py-5">
                <div>
                    <h4 class="card-title mb-1">Penjualan per {{ $seri['mode'] === 'hari' ? 'hari' : 'bulan' }}</h4>
                    <div class="text-muted fs-7">Batang = nilai penjualan, garis = jumlah transaksi. {{ $r['label'] }}</div>
                </div>
            </div>
            <div class="card-body">
                <div id="chart-penjualan" style="min-height: 340px;"></div>
            </div>
        </div>
    @endif

    {{-- ============ Tab ============ --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-6 fs-6" id="lp-tabs">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'transaksi' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-transaksi" data-tab="transaksi">
                Per Transaksi
                <span class="badge badge-light-primary ms-1">{{ number_format($transaksi->total(), 0, ',', '.') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'barang' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-barang" data-tab="barang">
                Per Barang
                <span class="badge badge-light-primary ms-1">{{ number_format($barang->total(), 0, ',', '.') }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $tab === 'transaksi' ? 'show active' : '' }}" id="tab-transaksi" role="tabpanel">
            @include('Laporan.Penjualan.partials._transaksi')
        </div>

        <div class="tab-pane fade {{ $tab === 'barang' ? 'show active' : '' }}" id="tab-barang" role="tabpanel">
            @include('Laporan.Penjualan.partials._barang')
        </div>
    </div>

    @can('penjualan-terjual.view')
        {{-- modal detail Terjual yang sama dengan halaman Pemasukan dan Laporan Keuangan --}}
        @include('Keuangan.Pemasukan.partials._modal_terjual')
    @endcan
@endsection

@push('scripts')
<script>
(function () {
    // select2 barang & kasir (bisa dicari). Manual supaya tidak ganda dengan auto-init Metronic.
    if ($.fn.select2) {
        $('#lp-barang').select2({ width: '220px', placeholder: 'Semua barang', allowClear: true });
        $('#lp-kasir').select2({ width: '190px', placeholder: 'Semua kasir', allowClear: true });
    }

    // ingat tab aktif: ikut terkirim saat filter diterapkan
    $('#lp-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        $('#lp-tab').val($(this).data('tab'));
    });

    // ---------- grafik ----------
    const el = document.getElementById('chart-penjualan');
    if (!el) return;

    if (typeof ApexCharts === 'undefined') {
        el.innerHTML = '<div class="text-center text-muted py-10">Grafik tidak bisa dimuat (ApexCharts belum tersedia). Angka tetap tersedia di tabel.</div>';
        return;
    }

    const rows   = @json($seri['rows']);
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const rp     = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v));
    const singkat = v => {
        const a = Math.abs(v);
        if (a >= 1e9) return (v / 1e9).toFixed(1).replace('.', ',') + ' M';
        if (a >= 1e6) return (v / 1e6).toFixed(1).replace('.', ',') + ' jt';
        if (a >= 1e3) return Math.round(v / 1e3) + ' rb';
        return String(v);
    };

    new ApexCharts(el, {
        chart: { type: 'line', height: 340, background: 'transparent', fontFamily: 'inherit',
                 foreColor: isDark ? '#9ca3af' : '#6b7280', toolbar: { show: false } },
        series: [
            { name: 'Penjualan',        type: 'column', data: rows.map(r => r.total) },
            { name: 'Jumlah transaksi', type: 'line',   data: rows.map(r => r.transaksi) },
        ],
        colors: ['#009ef7', '#ffc700'],
        stroke: { width: [0, 3], curve: 'smooth' },
        markers: { size: [0, 4] },
        plotOptions: { bar: { columnWidth: rows.length > 20 ? '70%' : '50%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        xaxis: {
            categories: rows.map(r => r.label),
            labels: { rotate: -45, rotateAlways: rows.length > 12, hideOverlappingLabels: true },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: [
            { seriesName: 'Penjualan', min: 0, labels: { formatter: singkat } },
            { seriesName: 'Jumlah transaksi', opposite: true, min: 0, forceNiceScale: true,
              labels: { formatter: v => String(Math.round(v)) } },
        ],
        grid: { borderColor: isDark ? '#2b2b40' : '#eff2f5', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'left' },
        tooltip: { shared: true, intersect: false, theme: isDark ? 'dark' : 'light',
                   y: [{ formatter: rp }, { formatter: v => v + ' transaksi' }] },
    }).render();
})();
</script>
@endpush
