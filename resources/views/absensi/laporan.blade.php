@extends('layouts.app') {{-- sesuaikan dengan layout Metronic yang sudah kamu pakai --}}

@section('title', 'Total Absensi')

@section('content')
<div class="row g-5">

    {{-- HEADER + FILTER TANGGAL --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-5">
                    <div>
                        <h2 class="mb-1">{{ $isOwnReport ? 'Laporan Kehadiran Saya' : 'Laporan Kehadiran ' . $reportUser->name }}</h2>
                        <div class="text-muted fs-7">
                            {{ $reportUser->name }} &middot; Periode {{ $from->translatedFormat('d M Y') }} - {{ $to->translatedFormat('d M Y') }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if ($canViewAll)
                            <a href="{{ route('absensi.laporan') }}" class="btn btn-light-primary btn-sm">
                                &larr; Kembali ke rekap
                            </a>
                        @endif
                        <a href="{{ url()->full() }}" class="btn btn-light btn-sm">
                            <i class="ki-duotone ki-arrows-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Muat ulang
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('absensi.laporan') }}" class="row g-3 align-items-end mb-5">
                    @foreach ($userParam as $paramName => $paramValue)
                        <input type="hidden" name="{{ $paramName }}" value="{{ $paramValue }}">
                    @endforeach
                    <div class="col-auto">
                        <label class="form-label fs-8 text-uppercase text-muted mb-1">Dari Tanggal</label>
                        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label class="form-label fs-8 text-uppercase text-muted mb-1">Sampai Tanggal</label>
                        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ki-duotone ki-filter fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Terapkan
                        </button>
                    </div>
                </form>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted fs-8 text-uppercase me-2">Cepat:</span>
                    @foreach ($quickFilters as $key => $filter)
                        <a href="{{ route('absensi.laporan', ['quick' => $key] + $userParam) }}"
                           class="btn btn-sm {{ $activeQuick === $key ? 'btn-success' : 'btn-light' }}">
                            {{ $filter['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- PERBANDINGAN DENGAN PERIODE SEBELUMNYA --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3 fs-7 text-muted">
                Dibanding {{ $prevFrom->translatedFormat('d M Y') }} - {{ $prevTo->translatedFormat('d M Y') }}:
                Kehadiran
                <span class="fw-bold {{ $kehadiranSelisih >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $kehadiranSelisih >= 0 ? '+' : '' }}{{ number_format($kehadiranSelisih, 1, ',', '.') }} poin
                </span>
                (dulu {{ number_format($prevKehadiranPersen, 1, ',', '.') }}%)
            </div>
        </div>
    </div>

    {{-- 5 KARTU STATISTIK --}}
    <div class="col-12">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-4">
            <div class="col">
                <div class="card h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Hadir</div>
                        <div class="fs-2 fw-bold">{{ $hadirCount }} <span class="fs-7 text-muted fw-normal">hari</span></div>
                        <div class="fs-8 text-muted mt-1">{{ number_format($kehadiranPersen, 1, ',', '.') }}% dari hari kerja</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Jam Kerja</div>
                        <div class="fs-2 fw-bold">{{ $jamKerjaLabel }}</div>
                        <div class="fs-8 text-muted mt-1">{{ number_format($jamKerjaPersen, 1, ',', '.') }}% dari target {{ $targetJamKerja }}j</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-start border-4 border-info">
                    <div class="card-body">
                        <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Izin & Sakit</div>
                        <div class="fs-2 fw-bold">0 <span class="fs-7 text-muted fw-normal">hari</span></div>
                        <div class="fs-8 text-muted mt-1">Fitur izin/sakit belum tersedia</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Lupa Absen Pulang</div>
                        <div class="fs-2 fw-bold">{{ $lupaPulangCount }} <span class="fs-7 text-muted fw-normal">hari</span></div>
                        <div class="fs-8 text-muted mt-1">dari {{ $hadirCount }} hari hadir</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Tanpa Keterangan</div>
                        <div class="fs-2 fw-bold">{{ $tanpaKeteranganCount }} <span class="fs-7 text-muted fw-normal">hari</span></div>
                        <div class="fs-8 text-muted mt-1">dari {{ $workDaysCount }} hari kerja</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RINCIAN HARIAN --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header flex-wrap gap-3">
                <div>
                    <h3 class="card-title mb-0">Rincian Harian</h3>
                    <div class="text-muted fs-8">Jam masuk & pulang berdasarkan stempel waktu server saat absen.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="tab-rincian-kerja" class="btn btn-sm btn-success" onclick="switchRincianTab('kerja')">Hari kerja</button>
                    <button type="button" id="tab-rincian-perhatian" class="btn btn-sm btn-light" onclick="switchRincianTab('perhatian')">Perlu perhatian</button>
                    <button type="button" id="tab-rincian-semua" class="btn btn-sm btn-light" onclick="switchRincianTab('semua')">Semua tanggal</button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2 fs-8 text-muted">
                        Tampilkan
                        <select id="rincian-page-size" class="form-select form-select-sm w-auto">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="9999">Semua</option>
                        </select>
                        baris
                    </div>
                    <input type="text" id="rincian-search-input" class="form-control form-control-sm w-250px" placeholder="Cari tanggal, hari, lokasi...">
                </div>

                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle" id="rincian-table">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Status</th>
                                <th>Absen Masuk</th>
                                <th>Absen Pulang</th>
                                <th>Durasi Kerja</th>
                                <th>Lokasi / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rincianHarian as $row)
                                @php
                                    $badgeClass = match ($row['status']) {
                                        'Hadir' => 'badge-light-success',
                                        'Lupa Absen Pulang' => 'badge-light-warning',
                                        'Tanpa Keterangan' => 'badge-light-danger',
                                        default => 'badge-light-secondary', // Libur & Belum Mulai
                                    };
                                    $searchKey = strtolower(
                                        $row['date']->translatedFormat('d M Y') . ' ' .
                                        $row['date']->translatedFormat('l') . ' ' .
                                        $row['lokasi']
                                    );
                                @endphp
                                <tr class="rincian-row"
                                    data-workday="{{ $row['is_workday'] ? '1' : '0' }}"
                                    data-attention="{{ in_array($row['status'], ['Lupa Absen Pulang', 'Tanpa Keterangan']) ? '1' : '0' }}"
                                    data-search="{{ $searchKey }}">
                                    <td>{{ $row['date']->translatedFormat('d M Y') }}</td>
                                    <td>{{ $row['date']->translatedFormat('l') }}</td>
                                    <td><span class="badge {{ $badgeClass }}">{{ $row['status'] }}</span></td>
                                    <td>{{ $row['masuk']?->recorded_at->format('H:i') ?? '-' }}</td>
                                    <td>{{ $row['pulang']?->recorded_at->format('H:i') ?? '-' }}</td>
                                    <td>{{ $row['durasi'] }}</td>
                                    <td>{{ $row['lokasi'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">Tidak ada data pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div id="rincian-page-info" class="text-muted fs-8"></div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="rincian-prev" class="btn btn-sm btn-light">Sebelumnya</button>
                        <span id="rincian-page-label" class="fs-7 fw-bold px-2"></span>
                        <button type="button" id="rincian-next" class="btn btn-sm btn-light">Berikutnya</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('rincian-search-input');
    const pageSizeSelect = document.getElementById('rincian-page-size');
    const prevBtn = document.getElementById('rincian-prev');
    const nextBtn = document.getElementById('rincian-next');
    const pageInfo = document.getElementById('rincian-page-info');
    const pageLabel = document.getElementById('rincian-page-label');

    let currentTab = 'kerja';
    let currentPage = 1;

    function rows() {
        return Array.from(document.querySelectorAll('#rincian-table tbody tr.rincian-row'));
    }

    function applyFilters() {
        const keyword = searchInput.value.trim().toLowerCase();
        const pageSize = parseInt(pageSizeSelect.value, 10);

        // 1) baris yang cocok dengan tab + pencarian
        const matching = rows().filter((row) => {
            const matchesTab =
                currentTab === 'semua' ? true :
                currentTab === 'kerja' ? row.dataset.workday === '1' :
                row.dataset.attention === '1';
            const matchesSearch = !keyword || row.dataset.search.includes(keyword);
            return matchesTab && matchesSearch;
        });

        // 2) hitung halaman, jaga currentPage tetap valid
        const totalPages = Math.max(1, Math.ceil(matching.length / pageSize));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = startIdx + pageSize;
        const visibleSet = new Set(matching.slice(startIdx, endIdx));

        // 3) tampilkan hanya baris di halaman aktif
        rows().forEach((row) => row.classList.toggle('d-none', !visibleSet.has(row)));

        // 4) update info & tombol
        const from = matching.length === 0 ? 0 : startIdx + 1;
        const to = Math.min(endIdx, matching.length);
        pageInfo.textContent = `Menampilkan ${from}-${to} dari ${matching.length} baris`;
        pageLabel.textContent = `${currentPage} / ${totalPages}`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    window.switchRincianTab = function (tab) {
        currentTab = tab;
        currentPage = 1;
        ['kerja', 'perhatian', 'semua'].forEach((t) => {
            const btn = document.getElementById('tab-rincian-' + t);
            btn.classList.toggle('btn-success', t === tab);
            btn.classList.toggle('btn-light', t !== tab);
        });
        applyFilters();
    };

    searchInput.addEventListener('input', () => { currentPage = 1; applyFilters(); });
    pageSizeSelect.addEventListener('change', () => { currentPage = 1; applyFilters(); });
    prevBtn.addEventListener('click', () => { currentPage--; applyFilters(); });
    nextBtn.addEventListener('click', () => { currentPage++; applyFilters(); });

    applyFilters();
});
</script>
@endpush