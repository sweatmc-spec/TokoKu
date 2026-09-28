@extends('layouts.app') {{-- sesuaikan dengan layout Metronic yang sudah kamu pakai --}}

@section('title', 'Rekap Absensi Karyawan')

@section('content')
<div class="row g-5">

    {{-- HEADER + FILTER --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-5">
                    <div>
                        <h2 class="mb-1">Rekap Absensi Karyawan</h2>
                        <div class="text-muted fs-7">
                            Periode {{ $from->translatedFormat('d M Y') }} - {{ $to->translatedFormat('d M Y') }}
                            &middot; {{ $users->total() }} karyawan
                        </div>
                    </div>
                    <a href="{{ url()->full() }}" class="btn btn-light btn-sm">
                        <i class="ki-duotone ki-arrows-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Muat ulang
                    </a>
                </div>

                <form method="GET" action="{{ route('absensi.laporan') }}" class="row g-3 align-items-end mb-5">
                    <div class="col-12 col-md-4">
                        <label class="form-label fs-8 text-uppercase text-muted mb-1">Cari Nama</label>
                        <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Ketik nama karyawan...">
                    </div>
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
                        <a href="{{ route('absensi.laporan', array_filter(['quick' => $key, 'q' => $search])) }}"
                           class="btn btn-sm {{ $activeQuick === $key ? 'btn-success' : 'btn-light' }}">
                            {{ $filter['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL REKAP --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="text-muted fs-8 mb-3">
                    Hari kerja Senin-Jumat, dihitung mulai dari hari pertama tiap karyawan absen. Klik "Detail" untuk rincian harian.
                </div>

                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>#</th>
                                <th>Nama Karyawan</th>
                                <th>Hadir</th>
                                <th>Lupa Absen Pulang</th>
                                <th>Tanpa Keterangan</th>
                                <th>Jam Kerja</th>
                                <th>Kehadiran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $i => $row)
                                <tr>
                                    <td>{{ $users->firstItem() + $i }}</td>
                                    <td class="fw-bold">{{ $row['user']->name }}</td>
                                    <td>{{ $row['stat']['hadir'] }} <span class="text-muted fs-8">/ {{ $row['stat']['hari_kerja'] }} hari</span></td>
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
                                           class="btn btn-sm btn-light-primary">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        {{ $search !== '' ? 'Tidak ada karyawan dengan nama tersebut.' : 'Belum ada karyawan.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div class="text-muted fs-8">
                        Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} karyawan
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if ($users->onFirstPage())
                            <span class="btn btn-sm btn-light disabled">Sebelumnya</span>
                        @else
                            <a href="{{ $users->previousPageUrl() }}" class="btn btn-sm btn-light">Sebelumnya</a>
                        @endif

                        <span class="fs-7 fw-bold px-2">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>

                        @if ($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}" class="btn btn-sm btn-light">Berikutnya</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Berikutnya</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection