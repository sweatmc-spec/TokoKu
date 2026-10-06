@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    // ---- Data dari DashboardController. Semua opsional: kalau kosong, halaman tampil dengan empty state. ----
    $user            = auth()->user();
    $isAdmin         = $isAdmin ?? false;                 // true = tampilan admin, false = tampilan karyawan
    $stats           = $stats ?? [];                      // admin: karyawan, hadir, izin, belum_absen
    $myStats         = $myStats ?? [];                    // karyawan: hadir, hari_kerja, sakit, izin, tanpa_keterangan, lupa_pulang, jam_kerja, persen
    $attendanceToday = $attendanceToday ?? ['label' => 'Belum absen', 'badge' => 'badge-light', 'masuk' => null, 'pulang' => null];
    $attendanceChart = $attendanceChart ?? ['labels' => [], 'series' => [], 'details' => []];
    $statusChart     = $statusChart ?? ['labels' => [], 'values' => []];
    $todayAttendance = collect($todayAttendance ?? []);   // name, status, badge, masuk, pulang, jam_kerja
    $myRecent        = collect($myRecent ?? []);          // tanggal, masuk, pulang, jam_kerja, status, badge

    $absenUrl        = $absenUrl ?? (Route::has('absensi.index') ? route('absensi.index') : url('/absensi'));

    $roleName = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null;
    $hour     = now()->hour;
    $sapa     = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $fmt      = fn ($n) => number_format((float) $n, 0, ',', '.');

    if ($isAdmin) {
        $cards = [
            ['label' => 'Total Karyawan', 'value' => $fmt($stats['karyawan'] ?? 0),
             'sub' => 'terdaftar',         'color' => 'primary', 'icon' => 'ki-profile-user', 'paths' => 4],
            ['label' => 'Hadir Hari Ini', 'value' => $fmt($stats['hadir'] ?? 0),
             'sub' => 'dari ' . $fmt($stats['karyawan'] ?? 0) . ' karyawan', 'color' => 'success', 'icon' => 'ki-check-circle', 'paths' => 2],
            ['label' => 'Sakit / Izin',   'value' => $fmt($stats['izin'] ?? 0),
             'sub' => 'hari ini',          'color' => 'warning', 'icon' => 'ki-information-5', 'paths' => 3],
            ['label' => 'Belum Absen',    'value' => $fmt($stats['belum_absen'] ?? 0),
             'sub' => 'hari ini',          'color' => 'danger', 'icon' => 'ki-cross-circle', 'paths' => 2],
        ];
    } else {
        $cards = [
            ['label' => 'Hadir Bulan Ini', 'value' => $fmt($myStats['hadir'] ?? 0) . ' / ' . $fmt($myStats['hari_kerja'] ?? 0),
             'sub' => 'Kehadiran ' . number_format((float) ($myStats['persen'] ?? 0), 1, ',', '.') . '%',
             'color' => 'success', 'icon' => 'ki-check-circle', 'paths' => 2],
            ['label' => 'Sakit / Izin',    'value' => $fmt(($myStats['sakit'] ?? 0) + ($myStats['izin'] ?? 0)),
             'sub' => $fmt($myStats['sakit'] ?? 0) . ' sakit · ' . $fmt($myStats['izin'] ?? 0) . ' izin/cuti',
             'color' => 'warning', 'icon' => 'ki-information-5', 'paths' => 3],
            ['label' => 'Tanpa Keterangan', 'value' => $fmt($myStats['tanpa_keterangan'] ?? 0) . ' hari',
             'sub' => 'Lupa absen pulang ' . $fmt($myStats['lupa_pulang'] ?? 0) . ' hari',
             'color' => 'danger', 'icon' => 'ki-cross-circle', 'paths' => 2],
            ['label' => 'Jam Kerja',       'value' => $myStats['jam_kerja'] ?? '0j 0m',
             'sub' => 'bulan ini',
             'color' => 'primary', 'icon' => 'ki-time', 'paths' => 2],
        ];
    }
@endphp

<div class="row g-5 g-xl-8">

    {{-- SAMBUTAN + ABSENSI HARI INI --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-6">
                <div>
                    <div class="text-gray-500 fs-7 fw-semibold mb-1">{{ now()->translatedFormat('l, d F Y') }}</div>
                    <h2 class="fw-bold text-gray-900 mb-2">{{ $sapa }}, {{ $user->name }}</h2>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        @if ($roleName)
                            <span class="badge badge-light-primary fs-7">{{ \Illuminate\Support\Str::headline($roleName) }}</span>
                        @endif
                        <span class="text-gray-600 fs-7">
                            {{ $isAdmin ? 'Ringkasan kehadiran karyawan hari ini.' : 'Ringkasan kehadiran Anda bulan ini.' }}
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-8 border border-dashed border-gray-300 rounded px-6 py-4">
                    <div>
                        <div class="text-gray-500 fs-7 fw-semibold mb-2">Absensi hari ini</div>
                        <span class="badge {{ $attendanceToday['badge'] ?? 'badge-light' }} fs-6">{{ $attendanceToday['label'] ?? 'Belum absen' }}</span>
                    </div>
                    <div>
                        <div class="text-gray-500 fs-7 fw-semibold mb-1">Masuk</div>
                        <div class="fs-4 fw-bold text-gray-800">{{ $attendanceToday['masuk'] ?? '--:--' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500 fs-7 fw-semibold mb-1">Pulang</div>
                        <div class="fs-4 fw-bold text-gray-800">{{ $attendanceToday['pulang'] ?? '--:--' }}</div>
                    </div>
                    <a href="{{ $absenUrl }}"
                       class="btn btn-sm {{ ($attendanceToday['label'] ?? '') === 'Belum Absen' ? 'btn-primary' : 'btn-light-primary' }}">
                        {{ ($attendanceToday['label'] ?? '') === 'Belum Absen' ? 'Absen sekarang' : 'Buka absensi' }}
                        <i class="ki-duotone ki-arrow-right fs-5 ms-1 me-0"><span class="path1"></span><span class="path2"></span></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KARTU STATISTIK --}}
    @foreach ($cards as $card)
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <span class="text-gray-600 fs-6 fw-semibold">{{ $card['label'] }}</span>
                        <i class="ki-duotone {{ $card['icon'] }} fs-2x text-{{ $card['color'] }}">
                            @for ($i = 1; $i <= $card['paths']; $i++)<span class="path{{ $i }}"></span>@endfor
                        </i>
                    </div>
                    <div class="fs-2hx fw-bold text-gray-900 lh-1">{{ $card['value'] }}</div>
                    <div class="text-gray-500 fs-7 mt-3">{{ $card['sub'] }}</div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- GRAFIK PRIBADI (semua akun hanya melihat miliknya sendiri) --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column align-items-start">
                    <h3 class="fw-bold m-0">Kehadiran Saya, 7 Hari Terakhir</h3>
                    <div class="text-muted fs-7 mt-1">Status tiap hari, termasuk akhir pekan (Libur). Arahkan kursor untuk rincian.</div>
                </div>
            </div>
            <div class="card-body pt-2">
                <div id="chartKehadiran"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column align-items-start">
                    <h3 class="fw-bold m-0">Kehadiran Bulan Ini</h3>
                    <div class="text-muted fs-7 mt-1">Komposisi hari kerja Anda.</div>
                </div>
            </div>
            <div class="card-body pt-2">
                @if (array_sum($statusChart['values'] ?? []) > 0)
                    <div id="chartStatus"></div>
                @else
                    <div class="text-center py-15">
                        <i class="ki-duotone ki-profile-user fs-3x text-gray-400 mb-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                        </i>
                        <div class="text-gray-800 fw-bold fs-5">Belum ada data bulan ini</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($isAdmin)
        {{-- ABSENSI HARI INI --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold m-0">Absensi Hari Ini</h3>
                        <div class="text-muted fs-7 mt-1">Status tiap karyawan hari ini.</div>
                    </div>
                    @if (Route::has('absensi.laporan'))
                        <div class="card-toolbar">
                            <a href="{{ route('absensi.laporan') }}" class="btn btn-sm btn-light-primary">
                                <i class="ki-duotone ki-eye fs-4">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                                Lihat rekap
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body py-4">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                    <th class="w-50px">#</th>
                                    <th class="min-w-175px">Nama karyawan</th>
                                    <th>Status</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Jam kerja</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 fw-semibold">
                                @forelse ($todayAttendance as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="text-gray-800 fw-bold">{{ data_get($row, 'name') }}</td>
                                        <td><span class="badge {{ data_get($row, 'badge', 'badge-light') }} fs-7">{{ data_get($row, 'status') }}</span></td>
                                        <td>{{ data_get($row, 'masuk') ?: '--:--' }}</td>
                                        <td>{{ data_get($row, 'pulang') ?: '--:--' }}</td>
                                        <td>{{ data_get($row, 'jam_kerja') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-15">
                                            <i class="ki-duotone ki-profile-user fs-3x text-gray-400 mb-3">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                            </i>
                                            <div class="text-gray-800 fw-bold fs-5">Belum ada karyawan</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- RIWAYAT ABSENSI SAYA --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold m-0">Riwayat Absensi Saya</h3>
                        <div class="text-muted fs-7 mt-1">Catatan kehadiran terbaru.</div>
                    </div>
                </div>
                <div class="card-body py-4">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                    <th class="min-w-150px">Tanggal</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Jam kerja</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 fw-semibold">
                                @forelse ($myRecent as $row)
                                    <tr>
                                        <td class="text-gray-800 fw-bold">{{ data_get($row, 'tanggal') }}</td>
                                        <td>{{ data_get($row, 'masuk') ?: '--:--' }}</td>
                                        <td>{{ data_get($row, 'pulang') ?: '--:--' }}</td>
                                        <td>{{ data_get($row, 'jam_kerja') ?: '-' }}</td>
                                        <td class="text-end">
                                            <span class="badge {{ data_get($row, 'badge', 'badge-light') }} fs-7">{{ data_get($row, 'status') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-0">
                                            <a href="{{ $absenUrl }}" class="d-block text-center text-decoration-none py-15">
                                                <i class="ki-duotone ki-profile-user fs-3x text-gray-400 mb-3">
                                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                                </i>
                                                <div class="text-gray-800 fw-bold fs-5">Belum ada riwayat absensi</div>
                                                <div class="text-muted fs-7 mt-1 mb-4">Klik di sini untuk mulai absen.</div>
                                                <span class="btn btn-sm btn-primary">Absen sekarang</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return; // ApexCharts ikut di plugins.bundle.js Metronic

    const css = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const textColor   = css('--bs-gray-500') || '#9a9cae';
    const bodyBg      = css('--bs-body-bg')  || '#15171c';

    const warna = {
        'Hadir':             css('--bs-success') || '#17c653',
        'Lupa absen pulang': css('--bs-warning') || '#f6c000',
        'Sakit / Izin':      css('--bs-info')    || '#7239ea',
        'Menunggu':          css('--bs-gray-500') || '#7e8299',
        'Tanpa keterangan':  css('--bs-danger')  || '#f8285a',
        'Libur':             css('--bs-gray-500') || '#7e8299',
        'Belum aktif':       css('--bs-gray-300') || '#464852',
    };

    // ---- Kehadiran saya, 7 hari terakhir (tiap hari = satu blok berwarna) ----
    const el1 = document.getElementById('chartKehadiran');
    if (el1) {
        const data = @json($attendanceChart);
        const seri = data.series.filter(s => s.data.some(v => v > 0)); // legend hanya status yang muncul

        new ApexCharts(el1, {
            chart: { type: 'bar', height: 300, stacked: true, toolbar: { show: false }, fontFamily: 'inherit', foreColor: textColor },
            series: seri,
            colors: seri.map(s => warna[s.name]),
            xaxis: { categories: data.labels, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { min: 0, max: 1, show: false },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 6, dataLabels: { orientation: 'vertical', position: 'center' } } },
            dataLabels: {
                enabled: true,
                formatter: (val, opt) => val ? opt.w.globals.seriesNames[opt.seriesIndex] : '',
                style: { fontSize: '12px', fontWeight: 600, colors: ['#fff'] },
            },
            grid: { show: false },
            legend: { position: 'top', horizontalAlign: 'right' },
            tooltip: {
                theme: 'dark',
                custom: function ({ dataPointIndex }) {
                    const d = data.details[dataPointIndex];
                    let html = '<div class="px-4 py-3"><div class="fw-bold fs-7 mb-1">' + d.tanggal + '</div>'
                             + '<div class="fs-7">' + d.status + '</div>';
                    if (d.masuk || d.pulang) {
                        html += '<div class="fs-8 mt-1 opacity-75">Masuk ' + (d.masuk || '--:--')
                              + ' &middot; Pulang ' + (d.pulang || '--:--')
                              + (d.durasi ? ' &middot; ' + d.durasi : '') + '</div>';
                    }
                    if (d.catatan) html += '<div class="fs-8 mt-1 opacity-75">' + d.catatan + '</div>';
                    return html + '</div>';
                },
            },
        }).render();
    }

    // ---- Kehadiran bulan ini ----
    const el2 = document.getElementById('chartStatus');
    if (el2) {
        const data = @json($statusChart);
        new ApexCharts(el2, {
            chart: { type: 'donut', height: 300, fontFamily: 'inherit', foreColor: textColor },
            series: data.values.map(Number),
            labels: data.labels,
            colors: data.labels.map(l => warna[l]),
            stroke: { colors: [bodyBg], width: 2 },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Hari kerja' } } } } },
            tooltip: { theme: 'dark' },
        }).render();
    }
});
</script>
@endpush