@extends('layouts.app')

@section('title', 'Pengeluaran')

@push('styles')
    <style>
        .stat-card { border-left: 4px solid var(--bs-danger); }
        .stat-value { font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    @php
        $rp       = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
        $filtered = $dari || $sampai || $kategori || $sumber;
    @endphp

    {{-- ============ Ringkasan (tidak ikut filter tabel) ============ --}}
    <div class="row g-5 mb-6">
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Hari ini</div>
                    <div class="fs-1 fw-bold stat-value text-danger">{{ $rp($summary['today']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ now()->translatedFormat('d F Y') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Bulan ini</div>
                    <div class="fs-1 fw-bold stat-value text-danger">{{ $rp($summary['month']) }}</div>
                    <div class="text-muted fs-8 mt-1">
                        {{ now()->translatedFormat('F Y') }} &middot; bulan lalu {{ $rp($summary['last_month']) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-2">Per kategori bulan ini</div>
                    <div style="max-height:84px; overflow-y:auto;">
                        @forelse ($perKategori as $k)
                            <div class="d-flex justify-content-between fs-7 mb-1">
                                <span>{{ $k->nama }}</span>
                                <span class="fw-semibold stat-value">{{ $rp($k->total_bulan_ini) }}</span>
                            </div>
                        @empty
                            <div class="text-muted fs-7">Belum ada pengeluaran bulan ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- ============ Judul + filter + tambah ============ --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-4 py-5">
            <div>
                <h4 class="card-title mb-1">Pengeluaran</h4>
                <div class="text-muted fs-7">Kulakan tercatat otomatis saat Cek Paket selesai. Biaya operasional diinput manual.</div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-3">
                <form method="GET" action="{{ route('keuangan.pengeluaran.index') }}" class="d-flex align-items-center flex-wrap gap-2">
                    <input type="date" name="dari" value="{{ $dari }}" class="form-control form-control-sm w-150px" title="Dari tanggal">
                    <input type="date" name="sampai" value="{{ $sampai }}" class="form-control form-control-sm w-150px" title="Sampai tanggal">
                    <select name="kategori" class="form-select form-select-sm w-175px">
                        <option value="">Semua kategori</option>
                        @foreach ($kategoris as $k)
                            <option value="{{ $k->id }}" @selected((int) $kategori === $k->id)>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    <select name="sumber" class="form-select form-select-sm w-150px">
                        <option value="">Semua sumber</option>
                        <option value="pembelian" @selected($sumber === 'pembelian')>Pembelian</option>
                        <option value="operasional" @selected($sumber === 'operasional')>Operasional</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                    @if ($filtered)
                        <a href="{{ route('keuangan.pengeluaran.index') }}" class="btn btn-sm btn-light">Reset</a>
                    @endif
                </form>

                @can('keuangan-pengeluaran.create')
                    <button type="button" class="btn btn-sm btn-danger text-nowrap d-inline-flex align-items-center gap-1"
                            data-bs-toggle="modal" data-bs-target="#modal-pengeluaran">
                        <i class="ki-outline ki-plus fs-4"></i> Tambah Pengeluaran
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
                            <th>Kategori</th>
                            <th>Sumber</th>
                            <th>Nama Sales</th>
                            <th>Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Bukti</th>
                            <th class="pe-6" style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengeluarans as $p)
                            <tr>
                                <td class="ps-6">{{ $pengeluarans->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $p->tanggal->format('d/m/Y') }}</td>
                                <td><span class="badge badge-light-dark">{{ $p->kategori->nama }}</span></td>
                                <td>
                                    @if ($p->isOperasional())
                                        <span class="badge badge-light-info">Operasional</span>
                                    @else
                                        <span class="badge badge-light-warning">Pembelian</span>
                                    @endif
                                </td>
                                <td>{{ $p->purchase?->sales?->name ?? '-' }}</td>
                                <td>
                                    @if ($p->purchase)
                                        {{-- Pembelian: kode paket mengarah ke Cek Paket terkait --}}
                                        @can('cek-paket.view')
                                            <a href="{{ route('purchases.show', $p->purchase) }}" class="fw-semibold font-monospace">{{ $p->purchase->code }}</a>
                                        @else
                                            <span class="fw-semibold font-monospace">{{ $p->purchase->code }}</span>
                                        @endcan
                                        <div class="text-muted fs-8">{{ $p->keterangan }}</div>
                                    @else
                                        {{ $p->keterangan ?: '-' }}
                                    @endif
                                </td>
                                <td class="text-end fw-semibold text-danger text-nowrap">{{ $rp($p->nominal) }}</td>
                                <td class="text-center">
                                    @if ($p->bukti)
                                        <a href="{{ route('keuangan.pengeluaran.bukti', $p) }}" target="_blank" rel="noopener"
                                           class="btn btn-sm btn-light-primary py-1 px-3">Lihat</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="pe-6">
                                    @if ($p->isOperasional())
                                        <div class="d-flex align-items-center gap-2">
                                            @can('keuangan-pengeluaran.edit')
                                                <button type="button" title="Edit"
                                                        class="btn btn-sm btn-icon border-0 bg-warning-subtle text-warning-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#modal-pengeluaran"
                                                        data-url="{{ route('keuangan.pengeluaran.update', $p) }}"
                                                        data-tanggal="{{ $p->tanggal->format('Y-m-d') }}"
                                                        data-kategori="{{ $p->kategori_pengeluaran_id }}"
                                                        data-keterangan="{{ $p->keterangan }}"
                                                        data-nominal="{{ $p->nominal }}"
                                                        data-bukti="{{ $p->bukti ? route('keuangan.pengeluaran.bukti', $p) : '' }}">
                                                    <i class="ki-outline ki-pencil fs-4 text-warning"></i>
                                                </button>
                                            @endcan
                                            @can('keuangan-pengeluaran.delete')
                                                <button type="button" title="Hapus"
                                                        class="btn btn-sm btn-icon border-0 bg-danger-subtle text-danger-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-url="{{ route('keuangan.pengeluaran.destroy', $p) }}"
                                                        data-name="pengeluaran {{ $p->kategori->nama }} sebesar {{ $rp($p->nominal) }} tanggal {{ $p->tanggal->format('d/m/Y') }}">
                                                    <i class="ki-outline ki-trash fs-4 text-danger"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @else
                                        <span class="text-muted fs-8 d-inline-flex align-items-center gap-1"
                                              title="Nominal mengikuti Cek Paket">
                                            <i class="ki-outline ki-lock fs-5"></i> Otomatis
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-8">
                                    {{ $filtered ? 'Tidak ada pengeluaran yang cocok dengan filter.' : 'Belum ada pengeluaran. Kulakan akan tercatat otomatis saat Cek Paket selesai.' }}
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
                <strong class="text-danger stat-value">{{ $rp($filteredTotal) }}</strong>
                <span class="text-muted">({{ number_format($pengeluarans->total(), 0, ',', '.') }} baris)</span>
            </div>

            {{ $pengeluarans->links() }}
        </div>
    </div>

    @canany(['keuangan-pengeluaran.create', 'keuangan-pengeluaran.edit'])
        @include('Keuangan.Pengeluaran.partials._modal_form')
    @endcanany
    @include('master-data.partials.delete-modal', ['label' => 'Pengeluaran'])
@endsection

@push('scripts')
    @include('master-data.partials.scripts')
@endpush
