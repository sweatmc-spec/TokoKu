{{--
    Tab "Ringkasan". Dipakai di dalam Laporan.Keuangan.index (mewarisi $rp, $perSumber, $perKategori, $perBulan, $tahun).
    Grafik memakai ApexCharts bawaan Metronic; tabel tetap tampil walau grafik tidak termuat.
--}}

{{-- ============ 1. Pemasukan vs Pengeluaran per bulan (satu tahun) ============ --}}
<div class="card mb-6">
    <div class="card-header d-flex justify-content-between align-items-center py-5">
        <div>
            <h4 class="card-title mb-1">Pemasukan vs Pengeluaran per bulan</h4>
            <div class="text-muted fs-7">Tahun {{ $tahun }}. Garis biru = selisih (laba/rugi). Tidak bergantung pada periode di atas.</div>
        </div>
    </div>
    <div class="card-body">
        @if ($perBulan['ada_data'])
            <div id="chart-bulanan" style="min-height: 360px;"></div>
        @else
            <div class="text-center text-muted py-12">Belum ada data pada tahun {{ $tahun }}{{ $adaFilter ? ' dengan filter yang dipilih' : '' }}.</div>
        @endif
    </div>
</div>

<div class="row g-6 mb-6">
    {{-- ============ 2. Pengeluaran per kategori: donut + tabel ============ --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-5">
                <div>
                    <h4 class="card-title mb-1">Pengeluaran per kategori</h4>
                    <div class="text-muted fs-7">{{ $r['label'] }}</div>
                </div>
            </div>
            <div class="card-body">
                @if ($perKategori)
                    <div id="chart-kategori" style="min-height: 300px;"></div>
                @else
                    <div class="text-center text-muted py-12">Tidak ada pengeluaran pada periode ini.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-5">
                <h4 class="card-title">Rincian per kategori</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead class="bg-body-tertiary">
                            <tr class="text-uppercase fs-8 fw-semibold text-muted">
                                <th class="ps-6">Kategori</th>
                                <th class="text-end">Total</th>
                                <th class="text-end pe-6">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perKategori as $k)
                                <tr>
                                    <td class="ps-6 fw-semibold">{{ $k['nama'] }}
                                        <div class="text-muted fs-8 fw-normal">{{ number_format($k['jumlah'], 0, ',', '.') }} transaksi</div>
                                    </td>
                                    <td class="text-end text-danger fw-semibold text-nowrap">{{ $rp($k['total']) }}</td>
                                    <td class="text-end pe-6">{{ \App\Support\Laporan\Format::porsi($k['persen']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-8">Tidak ada pengeluaran pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($perKategori)
                            <tfoot>
                                <tr class="fw-bold">
                                    <td class="ps-6">Total</td>
                                    <td class="text-end text-danger text-nowrap">{{ $rp($r['pengeluaran']) }}</td>
                                    <td class="text-end pe-6">100%</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-6">
    {{-- ============ 3. Pemasukan per sumber ============ --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header py-5">
                <div>
                    <h4 class="card-title mb-1">Pemasukan per sumber</h4>
                    <div class="text-muted fs-7">{{ $r['label'] }}</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead class="bg-body-tertiary">
                            <tr class="text-uppercase fs-8 fw-semibold text-muted">
                                <th class="ps-6">Sumber</th>
                                <th class="text-end">Total</th>
                                <th class="text-end pe-6">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perSumber as $s)
                                <tr>
                                    <td class="ps-6">
                                        <span class="badge {{ $s['kode'] === 'penjualan' ? 'badge-light-primary' : 'badge-light-info' }}">{{ $s['label'] }}</span>
                                        <div class="text-muted fs-8 mt-1">{{ number_format($s['jumlah'], 0, ',', '.') }} transaksi</div>
                                    </td>
                                    <td class="text-end text-success fw-semibold text-nowrap">{{ $rp($s['total']) }}</td>
                                    <td class="text-end pe-6">{{ \App\Support\Laporan\Format::porsi($s['persen']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="ps-6">Total</td>
                                <td class="text-end text-success text-nowrap">{{ $rp($r['pemasukan']) }}</td>
                                <td class="text-end pe-6">{{ $r['pemasukan'] > 0 ? '100%' : '-' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ 4. Ringkasan per bulan ============ --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-5">
                <h4 class="card-title">Ringkasan per bulan, {{ $tahun }}</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead class="bg-body-tertiary">
                            <tr class="text-uppercase fs-8 fw-semibold text-muted">
                                <th class="ps-6">Bulan</th>
                                <th class="text-end">Pemasukan</th>
                                <th class="text-end">Pengeluaran</th>
                                <th class="text-end pe-6">Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perBulan['rows'] as $b)
                                @php $kosong = $b['pemasukan'] === 0 && $b['pengeluaran'] === 0; @endphp
                                <tr class="{{ $kosong ? 'text-muted' : '' }}">
                                    <td class="ps-6">{{ $b['label'] }}</td>
                                    <td class="text-end text-nowrap">{{ $kosong ? '-' : $rp($b['pemasukan']) }}</td>
                                    <td class="text-end text-nowrap">{{ $kosong ? '-' : $rp($b['pengeluaran']) }}</td>
                                    <td class="text-end pe-6 fw-semibold text-nowrap {{ $kosong ? '' : ($b['selisih'] >= 0 ? 'text-success' : 'text-danger') }}">
                                        {{ $kosong ? '-' : $rp($b['selisih']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="ps-6">Total {{ $tahun }}</td>
                                <td class="text-end text-success text-nowrap">{{ $rp($perBulan['total']['pemasukan']) }}</td>
                                <td class="text-end text-danger text-nowrap">{{ $rp($perBulan['total']['pengeluaran']) }}</td>
                                <td class="text-end pe-6 text-nowrap {{ $perBulan['total']['selisih'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($perBulan['total']['selisih']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const bulanan  = @json($perBulan['rows']);
    const kategori = @json($perKategori);

    const elBulanan  = document.getElementById('chart-bulanan');
    const elKategori = document.getElementById('chart-kategori');
    if (!elBulanan && !elKategori) return;

    const pesanTidakAda = (el) => {
        el.innerHTML = '<div class="text-center text-muted py-10">Grafik tidak bisa dimuat (ApexCharts belum tersedia). Angka tetap tersedia di tabel.</div>';
    };

    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const rp     = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v));
    const singkat = v => {
        const a = Math.abs(v);
        if (a >= 1e9) return (v / 1e9).toFixed(1).replace('.', ',') + ' M';
        if (a >= 1e6) return (v / 1e6).toFixed(1).replace('.', ',') + ' jt';
        if (a >= 1e3) return Math.round(v / 1e3) + ' rb';
        return String(v);
    };
    const dasar = {
        background: 'transparent',
        fontFamily: 'inherit',
        foreColor: isDark ? '#9ca3af' : '#6b7280',
        toolbar: { show: false },
    };
    const grid = { borderColor: isDark ? '#2b2b40' : '#eff2f5', strokeDashArray: 4 };

    function gambar() {
        if (typeof ApexCharts === 'undefined') {
            if (elBulanan) pesanTidakAda(elBulanan);
            if (elKategori) pesanTidakAda(elKategori);
            return;
        }

        if (elBulanan) {
            new ApexCharts(elBulanan, {
                chart: Object.assign({ type: 'line', height: 360 }, dasar),
                series: [
                    { name: 'Pemasukan',   type: 'column', data: bulanan.map(b => b.pemasukan) },
                    { name: 'Pengeluaran', type: 'column', data: bulanan.map(b => b.pengeluaran) },
                    { name: 'Selisih',     type: 'line',   data: bulanan.map(b => b.selisih) },
                ],
                colors: ['#50cd89', '#f1416c', '#009ef7'],
                stroke: { width: [0, 0, 3], curve: 'smooth' },
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                markers: { size: [0, 0, 4] },
                dataLabels: { enabled: false },
                xaxis: { categories: bulanan.map(b => b.label), axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: singkat } },
                grid: grid,
                legend: { position: 'top', horizontalAlign: 'left' },
                tooltip: { shared: true, intersect: false, theme: isDark ? 'dark' : 'light', y: { formatter: rp } },
            }).render();
        }

        if (elKategori && kategori.length) {
            new ApexCharts(elKategori, {
                chart: Object.assign({ type: 'donut', height: 320 }, dasar),
                series: kategori.map(k => k.total),
                labels: kategori.map(k => k.nama),
                colors: ['#f1416c', '#ffc700', '#009ef7', '#7239ea', '#50cd89', '#ff8a00', '#6c757d', '#0dcaf0'],
                dataLabels: { enabled: false },
                legend: { position: 'bottom' },
                stroke: { width: 0 },
                plotOptions: { pie: { donut: { size: '68%', labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: w => rp(w.globals.seriesTotals.reduce((a, b) => a + b, 0)),
                    },
                } } } },
                tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: rp } },
            }).render();
        }
    }

    // Tab Ringkasan tersembunyi saat halaman dibuka dari tab Rincian: gambar setelah tab ditampilkan
    // (grafik yang digambar di wadah tersembunyi lebarnya 0).
    const tabRingkasan = document.getElementById('tab-ringkasan');
    if (tabRingkasan && tabRingkasan.classList.contains('active')) {
        gambar();
    } else {
        let sudah = false;
        $('#lp-tabs a[data-tab="ringkasan"]').on('shown.bs.tab', function () {
            if (!sudah) { sudah = true; gambar(); }
        });
    }
})();
</script>
@endpush
