@extends('layouts.app')

@section('title', 'Pemasukan')

@push('styles')
    <style>
        .stat-card { border-left: 4px solid var(--bs-success); }
        .stat-value { font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    @php
        $rp       = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
        $change   = $summary['change'];
        $filtered = $dari || $sampai || $sumber;
    @endphp

    {{-- ============ Ringkasan (tidak ikut filter tabel) ============ --}}
    <div class="row g-5 mb-6">
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Hari ini</div>
                    <div class="fs-1 fw-bold stat-value text-success">{{ $rp($summary['today']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ now()->translatedFormat('d F Y') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Bulan ini</div>
                    <div class="fs-1 fw-bold stat-value text-success">{{ $rp($summary['month']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ now()->translatedFormat('F Y') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Dibanding bulan lalu</div>
                    @if ($change === null)
                        <div class="fs-1 fw-bold text-muted">-</div>
                    @else
                        <div class="fs-1 fw-bold stat-value {{ $change >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="ki-outline {{ $change >= 0 ? 'ki-arrow-up' : 'ki-arrow-down' }} fs-2"></i>
                            {{ number_format(abs($change), 1, ',', '.') }}%
                        </div>
                    @endif
                    <div class="text-muted fs-8 mt-1">
                        Bulan lalu {{ $rp($summary['last_month']) }}
                        @if ($summary['last_month'] <= 0)
                            (belum ada data)
                        @endif
                        <br>Bulan ini baru berjalan {{ $summary['days_elapsed'] }} hari
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- ============ Judul + filter + tambah ============ --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-4 py-5">
            <div>
                <h4 class="card-title mb-1">Pemasukan</h4>
                <div class="text-muted fs-7">Penjualan tercatat otomatis dari Terjual. Pemasukan lain diinput manual.</div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-3">
                <form method="GET" action="{{ route('keuangan.pemasukan.index') }}" class="d-flex align-items-center flex-wrap gap-2">
                    <input type="date" name="dari" value="{{ $dari }}" class="form-control form-control-sm w-150px" title="Dari tanggal">
                    <input type="date" name="sampai" value="{{ $sampai }}" class="form-control form-control-sm w-150px" title="Sampai tanggal">
                    <select name="sumber" class="form-select form-select-sm w-150px">
                        <option value="">Semua sumber</option>
                        <option value="penjualan" @selected($sumber === 'penjualan')>Penjualan</option>
                        <option value="manual" @selected($sumber === 'manual')>Manual</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                    @if ($filtered)
                        <a href="{{ route('keuangan.pemasukan.index') }}" class="btn btn-sm btn-light">Reset</a>
                    @endif
                </form>

                @can('keuangan-pemasukan.create')
                    <button type="button" class="btn btn-sm btn-success text-nowrap d-inline-flex align-items-center gap-1"
                            data-bs-toggle="modal" data-bs-target="#modal-pemasukan">
                        <i class="ki-outline ki-plus fs-4"></i> Tambah Pemasukan
                    </button>
                @endcan
            </div>
        </div>

        {{-- ============ Tabel ============ --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0">
                    <thead class="bg-body-tertiary">
                        <tr class="text-uppercase fs-8 fw-semibold text-muted">
                            <th class="ps-6" style="width:60px">No</th>
                            <th>Tanggal</th>
                            <th>Sumber</th>
                            <th>Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th class="pe-6" style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pemasukans as $p)
                            <tr>
                                <td class="ps-6">{{ $pemasukans->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $p->tanggal->format('d/m/Y') }}</td>
                                <td>
                                    @if ($p->isManual())
                                        <span class="badge badge-light-info">Manual</span>
                                    @else
                                        <span class="badge badge-light-primary">Penjualan</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p->terjual)
                                        {{-- Penjualan: kode transaksi membuka detail Terjual --}}
                                        @can('penjualan-terjual.view')
                                            <a href="#" class="fw-semibold font-monospace"
                                               data-bs-toggle="modal" data-bs-target="#modal-detail-terjual"
                                               data-url="{{ route('penjualan.terjual.show', $p->terjual) }}"
                                               data-code="{{ $p->terjual->code }}">{{ $p->terjual->code }}</a>
                                        @else
                                            <span class="fw-semibold font-monospace">{{ $p->terjual->code }}</span>
                                        @endcan
                                        <div class="text-muted fs-8">{{ $p->keterangan }}</div>
                                    @else
                                        {{ $p->keterangan }}
                                    @endif
                                </td>
                                <td class="text-end fw-semibold text-success text-nowrap">{{ $rp($p->nominal) }}</td>
                                <td class="pe-6">
                                    @if ($p->isManual())
                                        <div class="d-flex align-items-center gap-2">
                                            @can('keuangan-pemasukan.edit')
                                                <button type="button" title="Edit"
                                                        class="btn btn-sm btn-icon border-0 bg-warning-subtle text-warning-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#modal-pemasukan"
                                                        data-url="{{ route('keuangan.pemasukan.update', $p) }}"
                                                        data-tanggal="{{ $p->tanggal->format('Y-m-d') }}"
                                                        data-keterangan="{{ $p->keterangan }}"
                                                        data-nominal="{{ $p->nominal }}">
                                                    <i class="ki-outline ki-pencil fs-4 text-warning"></i>
                                                </button>
                                            @endcan
                                            @can('keuangan-pemasukan.delete')
                                                <button type="button" title="Hapus"
                                                        class="btn btn-sm btn-icon border-0 bg-danger-subtle text-danger-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-url="{{ route('keuangan.pemasukan.destroy', $p) }}"
                                                        data-name="pemasukan &quot;{{ $p->keterangan }}&quot; sebesar {{ $rp($p->nominal) }} tanggal {{ $p->tanggal->format('d/m/Y') }}">
                                                    <i class="ki-outline ki-trash fs-4 text-danger"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @else
                                        <span class="text-muted fs-8 d-inline-flex align-items-center gap-1"
                                              title="Diubah lewat halaman Terjual">
                                            <i class="ki-outline ki-lock fs-5"></i> Otomatis
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-8">
                                    {{ $filtered ? 'Tidak ada pemasukan yang cocok dengan filter.' : 'Belum ada pemasukan. Penjualan akan tercatat otomatis.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ Footer: total sesuai filter + pagination ============ --}}
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="fs-7">
                <span class="text-muted">Total {{ $filtered ? 'sesuai filter' : 'semua data' }}:</span>
                <strong class="text-success stat-value">{{ $rp($filteredTotal) }}</strong>
                <span class="text-muted">({{ number_format($pemasukans->total(), 0, ',', '.') }} baris)</span>
            </div>

            {{ $pemasukans->links() }}
        </div>
    </div>

    @canany(['keuangan-pemasukan.create', 'keuangan-pemasukan.edit'])
        @include('Keuangan.Pemasukan.partials._modal_form')
    @endcanany
    @can('penjualan-terjual.view')
        @include('Keuangan.Pemasukan.partials._modal_terjual')
    @endcan
    @include('master-data.partials.delete-modal', ['label' => 'Pemasukan'])
@endsection

@push('scripts')
    @include('master-data.partials.scripts')
@endpush
