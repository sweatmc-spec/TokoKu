@extends('layouts.app')

@section('title', 'Rekap Absensi Karyawan')

@section('content')
<div class="row g-5">

    {{-- HEADER + FILTER --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-6">
                    <div>
                        <h3 class="fw-bold mb-1">Rekap Absensi Karyawan</h3>
                        <div id="periodInfo" class="text-muted fs-7">
                            Periode {{ $from->translatedFormat('d M Y') }} - {{ $to->translatedFormat('d M Y') }}
                            &middot; {{ $users->total() }} karyawan
                        </div>
                    </div>
                    <button type="button" id="btnReload" class="btn btn-sm btn-light-primary">
                        <i class="ki-duotone ki-arrows-circle fs-4">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Muat ulang
                    </button>
                </div>

                <form id="filterForm" method="GET" action="{{ route('absensi.laporan') }}" class="row g-4 align-items-end mb-6">
                    <div class="col-12 col-md-5 col-lg-4">
                        <label for="q" class="form-label fs-7 fw-semibold text-gray-600 mb-2">Cari nama</label>
                        <div class="position-relative">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <input type="text" id="q" name="q" value="{{ $search }}"
                                   class="form-control form-control-solid ps-12"
                                   placeholder="Ketik nama karyawan..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="from" class="form-label fs-7 fw-semibold text-gray-600 mb-2">Dari tanggal</label>
                        <input type="text" id="from" name="from" value="{{ $from->format('Y-m-d') }}"
                               class="form-control form-control-solid" placeholder="Pilih tanggal" readonly>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="to" class="form-label fs-7 fw-semibold text-gray-600 mb-2">Sampai tanggal</label>
                        <input type="text" id="to" name="to" value="{{ $to->format('Y-m-d') }}"
                               class="form-control form-control-solid" placeholder="Pilih tanggal" readonly>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-duotone ki-filter fs-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Terapkan
                        </button>
                    </div>
                </form>

                <div id="quickFilters" data-active="{{ $activeQuick }}" class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-gray-500 fs-7 me-2">Cepat:</span>
                    @foreach ($quickFilters as $key => $filter)
                        <a href="{{ route('absensi.laporan', array_filter(['quick' => $key, 'q' => $search])) }}"
                           class="btn btn-sm js-load {{ $activeQuick === $key ? 'btn-primary' : 'btn-light' }}">
                            {{ $filter['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL REKAP --}}
    <div class="col-12">
        <div class="card" id="rekapTable">
            <div class="card-body">
                <div class="text-muted fs-7 mb-4">
                    Hari kerja Senin-Jumat, dihitung mulai dari hari pertama tiap karyawan absen. Klik "Detail" untuk rincian harian.
                </div>

                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                <th class="w-50px">#</th>
                                <th class="min-w-175px">Nama karyawan</th>
                                <th>Hari ini</th>
                                <th>Hadir</th>
                                <th>Sakit / Izin</th>
                                <th>Lupa absen pulang</th>
                                <th>Tanpa keterangan</th>
                                <th>Jam kerja</th>
                                <th>Kehadiran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($rows as $i => $row)
                                <tr>
                                    <td>{{ $users->firstItem() + $i }}</td>
                                    <td class="text-gray-800 fw-bold">{{ $row['user']->name }}</td>
                                    <td>
                                        <span class="badge {{ $row['hari_ini']['badge'] }}"
                                              @if ($row['hari_ini']['title']) title="{{ $row['hari_ini']['title'] }}" @endif>
                                            {{ $row['hari_ini']['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $row['stat']['hadir'] }} <span class="text-muted fs-8">/ {{ $row['stat']['hari_kerja'] }} hari</span></td>
                                    <td>
                                        {{ $row['stat']['sakit'] }} <span class="text-muted fs-8">sakit</span>
                                        &middot; {{ $row['stat']['izin'] }} <span class="text-muted fs-8">izin/cuti</span>
                                        @if ($row['stat']['menunggu'] > 0)
                                            <div class="text-warning fs-8">{{ $row['stat']['menunggu'] }} menunggu</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $row['stat']['lupa_pulang'] > 0 ? 'badge-light-warning' : 'badge-light-secondary' }}">
                                            {{ $row['stat']['lupa_pulang'] }} hari
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $row['stat']['tanpa_keterangan'] > 0 ? 'badge-light-danger' : 'badge-light-secondary' }}">
                                            {{ $row['stat']['tanpa_keterangan'] }} hari
                                        </span>
                                    </td>
                                    <td>{{ $row['jam_kerja'] }}</td>
                                    <td>{{ number_format($row['persen'], 1, ',', '.') }}%</td>
                                    <td class="text-end">
                                        <a href="{{ route('absensi.laporan', ['user_id' => $row['user']->id] + $periodParams) }}"
                                           class="btn btn-sm btn-light-primary">
                                            <i class="ki-duotone ki-eye fs-4">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                            </i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-15">
                                        <i class="ki-duotone ki-profile-user fs-3x text-gray-400 mb-3">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span><span class="path4"></span>
                                        </i>
                                        <div class="text-gray-800 fw-bold fs-5">
                                            {{ $search !== '' ? 'Karyawan tidak ditemukan' : 'Belum ada karyawan' }}
                                        </div>
                                        @if ($search !== '')
                                            <div class="text-muted fs-7 mt-1">Tidak ada karyawan dengan nama tersebut.</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div class="text-muted fs-7">
                        Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} karyawan
                    </div>
                    {{ $users->links('vendor.pagination.metronic') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Loading di tengah halaman --}}
<div id="pageLoading" class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center"
     style="z-index:1060; background: rgba(0, 0, 0, .35);">
    <div class="spinner-border text-primary w-50px h-50px" role="status">
        <span class="visually-hidden">Memuat...</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form     = document.getElementById('filterForm');
    const qInput   = document.getElementById('q');
    const loading  = document.getElementById('pageLoading');
    const swapIds  = ['periodInfo', 'quickFilters', 'rekapTable'];

    // ---- Datepicker (flatpickr bawaan Metronic) ----
    const fpBase = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd M Y',
        allowInput: false,
        locale: (flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default',
    };
    const clearQuick = () => { document.getElementById('quickFilters').dataset.active = ''; };

    const fpTo = flatpickr('#to', Object.assign({}, fpBase, {
        onChange: clearQuick,
    }));
    const fpFrom = flatpickr('#from', Object.assign({}, fpBase, {
        onChange: function (dates, str) { clearQuick(); fpTo.set('minDate', str); },
    }));
    fpTo.set('minDate', document.getElementById('from').value);

    // ---- Loading overlay ----
    const showLoading = () => { loading.classList.remove('d-none'); loading.classList.add('d-flex'); };
    const hideLoading = () => { loading.classList.add('d-none'); loading.classList.remove('d-flex'); };
    const sleep = ms => new Promise(r => setTimeout(r, ms));

    // ---- URL sesuai filter saat ini ----
    function buildUrl() {
        const p = new URLSearchParams();
        const q = qInput.value.trim();
        if (q) p.set('q', q);

        const quick = document.getElementById('quickFilters').dataset.active;
        if (quick) {
            p.set('quick', quick);
        } else {
            p.set('from', document.getElementById('from').value);
            p.set('to', document.getElementById('to').value);
        }
        return form.action + '?' + p.toString();
    }

    // ---- Muat halaman tanpa reload penuh ----
    let ctrl = null, seq = 0;

    async function load(url, withOverlay) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mine = ++seq;

        if (withOverlay) showLoading();
        else document.getElementById('rekapTable').classList.add('opacity-50');

        try {
            const [res] = await Promise.all([
                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: ctrl.signal,
                }),
                withOverlay ? sleep(500) : Promise.resolve(),
            ]);
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const html = await res.text();
            if (mine !== seq) return;

            const doc = new DOMParser().parseFromString(html, 'text/html');
            swapIds.forEach(id => {
                const fresh = doc.getElementById(id), old = document.getElementById(id);
                if (fresh && old) old.replaceWith(fresh);
            });

            const nf = doc.getElementById('from'), nt = doc.getElementById('to');
            if (nf) fpFrom.setDate(nf.value, false);
            if (nt) { fpTo.setDate(nt.value, false); }
            if (nf) fpTo.set('minDate', nf.value);

            history.replaceState(null, '', url);
        } catch (err) {
            if (err.name === 'AbortError') return;
            window.location.href = url; // fallback: reload biasa
        } finally {
            if (mine === seq) hideLoading();
        }
    }

    // ---- Cari otomatis (tanpa Enter) ----
    let timer = null;
    qInput.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => load(buildUrl(), false), 400);
    });

    // ---- Terapkan / Enter ----
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(timer);
        load(buildUrl(), true);
    });

    // ---- Muat ulang ----
    document.getElementById('btnReload').addEventListener('click', function () {
        clearTimeout(timer);
        load(window.location.href, true);
    });

    // ---- Filter cepat & paginasi ----
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a.js-load, #rekapTable .pagination a');
        if (!link) return;
        e.preventDefault();
        load(link.href, true);
    });
});
</script>
@endpush